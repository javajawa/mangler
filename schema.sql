DROP DATABASE "blog";
DROP ROLE blog;
DROP ROLE "www-data";

CREATE ROLE blog WITH NOLOGIN;
CREATE ROLE "www-data" WITH LOGIN;

CREATE DATABASE blog
  WITH OWNER = blog
       TEMPLATE template0
       ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_GB.UTF-8'
       LC_CTYPE = 'en_GB.UTF-8'
       CONNECTION LIMIT = -1;

\c blog;

GRANT ALL ON DATABASE blog TO postgres;
GRANT ALL ON DATABASE blog TO blog;

CREATE OR REPLACE PROCEDURAL LANGUAGE plpgsql;

BEGIN;

CREATE SCHEMA blog;

SET search_path = blog, pg_catalog;

-- Digest function from postgres-contrib
CREATE OR REPLACE FUNCTION digest(text, text)  RETURNS bytea LANGUAGE c IMMUTABLE STRICT AS '$libdir/pgcrypto', 'pg_digest';
CREATE OR REPLACE FUNCTION digest(bytea, text) RETURNS bytea LANGUAGE c IMMUTABLE STRICT AS '$libdir/pgcrypto', 'pg_digest';

CREATE TYPE status AS ENUM (
	'draft',
	'published'
);

-- Trigger for creating associated blobs for posts/comments
CREATE OR REPLACE FUNCTION "CREATE OR REPLACEBlob"() RETURNS trigger
	LANGUAGE plpgsql STRICT SECURITY DEFINER
	AS $$BEGIN
		INSERT INTO blob (post_id, post_data, search_data)
			VALUES (LASTVAL(), NULL, NULL);

		RETURN new;
	END;$$;

-- Password hashing function
CREATE OR REPLACE FUNCTION "hashPassword"(handle text, password text) RETURNS character
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$SELECT encode(digest('v7qZYgtBXEt4vOraV4p++OrZFXSwiJn3uvz11X7Pj9GE/tMuIelN4lWSINujWHIIYPbKjB27ANcnv75sMbaCdw==' || $1 || $2 || 'mangle', 'sha512'), 'hex')$_$;

-- Function for {x,ht}ml stripping tags from text
CREATE OR REPLACE FUNCTION "stripTags"(text) RETURNS text
	LANGUAGE sql
	AS $_$
		SELECT regexp_replace(regexp_replace($1, E'(?x)<[^>]*?(\s alt \s* = \s* ([\'"]) ([^>]*?) \2) [^>]*? >', E'\3'), E'(?x)(< [^>]*? >)', '', 'g')
	$_$;

-- User Table
CREATE TABLE "user" (
	user_id integer               NOT NULL,
	handle  character varying(48) NOT NULL,
	email   character varying(96) NOT NULL,
	pass    character(128),
	perms   integer               NOT NULL DEFAULT 0
);

CREATE SEQUENCE user_id_seq START WITH 1 INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;
ALTER SEQUENCE  user_id_seq OWNED BY "user".user_id;

ALTER TABLE ONLY "user" ALTER COLUMN   user_id SET DEFAULT nextval('user_id_seq'::regclass);
ALTER TABLE ONLY "user" ADD CONSTRAINT user_pk PRIMARY KEY (user_id);
ALTER TABLE ONLY "user" ADD CONSTRAINT email   UNIQUE (email);
ALTER TABLE ONLY "user" ADD CONSTRAINT handle  UNIQUE (handle);

CREATE INDEX "user-idx-perms" ON "user" USING btree (perms);

-- Post Table
CREATE TABLE "post" (
	post_id     integer               NOT NULL,
	"timestamp" timestamp             NOT NULL,
	user_id     integer               NOT NULL,
	title       character varying(64) NOT NULL DEFAULT ''::character varying,
	slug        character varying(48)          DEFAULT NULL::character varying,
	status      status                DEFAULT 'draft'::status
);

CREATE SEQUENCE post_id_seq START WITH 1 INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;
ALTER SEQUENCE  post_id_seq OWNED BY post.post_id;

ALTER TABLE ONLY "post" ALTER COLUMN   post_id           SET DEFAULT nextval('post_id_seq'::regclass);
ALTER TABLE ONLY "post" ADD CONSTRAINT post_pk           PRIMARY KEY (post_id);
ALTER TABLE ONLY "post" ADD CONSTRAINT author            FOREIGN KEY (user_id) REFERENCES "user"(user_id);

CREATE INDEX "post-idx-slug"      ON "post" USING btree (slug);
CREATE INDEX "post-idx-timestamp" ON "post" USING btree ("timestamp", status);
CREATE INDEX "post-idx-title"     ON "post" USING btree (title);
CREATE INDEX "post-idx-user"      ON "post" USING btree (user_id);
CREATE UNIQUE INDEX "slug-check"  ON "post" USING btree (slug);

CREATE TRIGGER "CREATE OR REPLACE-post-blob" AFTER INSERT ON post FOR EACH ROW EXECUTE PROCEDURE "CREATE OR REPLACEBlob"();

-- Tags
CREATE TABLE "tags" (
	tag_id   integer               NOT NULL,
	tag      character varying(48) NOT NULL,
	tag_slug character varying(32) NOT NULL
);

ALTER TABLE ONLY "tags" ADD CONSTRAINT tags_pk PRIMARY KEY (tag_id);
ALTER TABLE ONLY "tags" ADD CONSTRAINT tag     UNIQUE (tag);
ALTER TABLE ONLY "tags" ADD CONSTRAINT slug    UNIQUE (tag_slug);

CREATE SEQUENCE tag_id_seq START WITH 1 INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;
ALTER SEQUENCE  tag_id_seq OWNED BY tags.tag_id;
ALTER TABLE ONLY "tags" ALTER COLUMN tag_id SET DEFAULT nextval('tag_id_seq'::regclass);

-- Post Blob Table
CREATE TABLE "blob" (
	post_id     integer  NOT NULL,
	post_data   text,
	search_data tsvector
);

ALTER TABLE ONLY "blob" ADD CONSTRAINT  blob_pk   PRIMARY KEY (post_id);
ALTER TABLE ONLY "blob" ADD CONSTRAINT "post-key" FOREIGN KEY (post_id) REFERENCES post(post_id) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE INDEX search_index ON "blob" USING gin (search_data);

-- Post Tags Table
CREATE TABLE "post_tags" (
	post_id integer NOT NULL,
	tag_id  integer NOT NULL
);

ALTER TABLE ONLY "post_tags" ADD CONSTRAINT post_tags_pk PRIMARY KEY (post_id, tag_id);
ALTER TABLE ONLY "post_tags" ADD CONSTRAINT post         FOREIGN KEY (post_id) REFERENCES post(post_id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY "post_tags" ADD CONSTRAINT tag          FOREIGN KEY (tag_id)  REFERENCES tags(tag_id) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE INDEX "tag-idx-tag-id" ON post_tags USING btree (tag_id);

-- Published Posts View
CREATE OR REPLACE VIEW "published_posts" AS
	SELECT
		"post".post_id AS id,
		"post"."timestamp",
		"post".title,
		"post".slug,
		"blob".post_data AS content,
		"user".handle AS "user",
		"user".email
	FROM
		"post" NATURAL JOIN "blob" NATURAL JOIN "user"
	WHERE
		"post".status = 'published'::status
	AND "post".timestamp <= NOW()
	ORDER BY
		"post"."timestamp" DESC
;

-- All Posts View
CREATE OR REPLACE VIEW "all_posts" AS
	SELECT
		"post".post_id AS id,
		"post"."timestamp",
		"post".title,
		"post".slug,
		"blob".post_data AS content,
		"user".handle AS "user",
		"user".email,
		"post".status
	FROM
		"post" NATURAL JOIN "blob" NATURAL JOIN "user"
	ORDER BY
		"post"."timestamp" DESC;

-- All Tags View
CREATE OR REPLACE VIEW "all_tags" AS
	SELECT
		"tags".tag,
		"tags".tag_slug,
		COUNT(post_id) AS itemcount,
		"tags".tag_id
	FROM
		tags NATURAL LEFT JOIN (post_tags NATURAL JOIN post)
	WHERE
		post.status = 'published'::status
	AND post.timestamp <= NOW()
	GROUP BY
		tags.tag_id, tags.tag, tags.tag_slug
;

SET search_path = public, blog, pg_catalog;

CREATE OR REPLACE FUNCTION "addTag"(_post integer, _tag integer) RETURNS void
LANGUAGE plpgsql STRICT SECURITY DEFINER
	AS $_$
		BEGIN
			IF 0 = (SELECT COUNT(*) FROM "blog"."post_tags" WHERE post_id = $1 AND tag_id = $2) THEN
				INSERT INTO "blog"."post_tags" VALUES ($1, $2);
			ELSE
				DELETE FROM "blog"."post_tags" WHERE post_id = $1 AND tag_id = $2;
			END IF;
		END;
	$_$;

CREATE OR REPLACE FUNCTION authenticate(handle text, password text) RETURNS blog."user"
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."user"
			WHERE "handle" = $1
			AND "pass" = "blog"."hashPassword"($1, $2)
			LIMIT 1;
	$_$;

CREATE OR REPLACE FUNCTION "countArchives"() RETURNS bigint
	LANGUAGE sql STABLE SECURITY DEFINER
	AS $_$
		SELECT COUNT(*) FROM "blog"."published_posts"
	$_$;

CREATE OR REPLACE FUNCTION "CREATE OR REPLACEPost"(aid integer) RETURNS integer
	LANGUAGE sql SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."post" (timestamp, user_id, status)
			VALUES (NOW(), $1, 'draft'::status);

		SELECT LASTVAL()::integer;
	$_$;

CREATE OR REPLACE FUNCTION "CREATE OR REPLACEPost"(handle character varying) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		SELECT "CREATE OR REPLACEPost"(
			(SELECT user_id FROM "blog"."user" WHERE handle = $1)
		);
	$_$;

CREATE OR REPLACE FUNCTION "deletePost"(_id int) RETURNS void
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		DELETE FROM "blog"."post" WHERE post_id = $1;
	$_$;

CREATE OR REPLACE FUNCTION "CREATE OR REPLACETag"(name text, slug text) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."tags" (tag, tag_slug) VALUES($1, $2) RETURNING tag_id;
	$_$;

CREATE OR REPLACE FUNCTION "CREATE OR REPLACEUser"(handle text, email text, pass text) RETURNS integer
	LANGUAGE sql SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."user" (handle, email, pass)
			VALUES ($1, $2, "blog"."hashPassword"($1 || $2, $3));

		SELECT LASTVAL()::integer AS user_id;
	$_$;

CREATE OR REPLACE FUNCTION "getArchives"(page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER ROWS 10
	AS $_$
		SELECT * FROM "blog"."published_posts" OFFSET (10 * $1) LIMIT 10;
	$_$;

CREATE OR REPLACE FUNCTION "getArchives"(tag_slug text, page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER COST 125 ROWS 10
	AS $_$
		SELECT "blog"."published_posts".*
		FROM "blog"."published_posts"
		LEFT JOIN ("blog"."post_tags" NATURAL JOIN "blog"."tags")
		ON "blog"."published_posts".id = "blog"."post_tags".post_id
		WHERE "tag_slug" = $1
		ORDER BY timestamp DESC
		OFFSET (10 * $2) LIMIT 10;
	$_$;

CREATE OR REPLACE FUNCTION "getPost"(_post_slug character varying) RETURNS blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."published_posts" WHERE slug = $1;
	$_$;

CREATE OR REPLACE FUNCTION "getPost"(_post_id integer) RETURNS blog.all_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."all_posts" WHERE id = $1;
	$_$;

CREATE OR REPLACE FUNCTION "getPosts"() RETURNS SETOF blog.all_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."all_posts";
	$_$;

CREATE OR REPLACE FUNCTION "getTag"(_tag character) RETURNS blog.all_tags
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."all_tags" WHERE tag_slug = $1;
	$_$;

CREATE OR REPLACE FUNCTION "getTags"() RETURNS SETOF blog.all_tags
	LANGUAGE sql STABLE SECURITY DEFINER
	AS $_$
		SELECT * FROM all_tags;
	$_$;

CREATE OR REPLACE FUNCTION "getTags"(_post_id integer) RETURNS SETOF blog.tags
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT "blog"."tags".*
		FROM "blog"."post_tags" NATURAL JOIN "blog"."tags"
		WHERE post_id = $1;
	$_$;

CREATE OR REPLACE FUNCTION "getUser"(_handle character) RETURNS blog."user"
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."user" WHERE handle = $1 LIMIT 1;
	$_$;

CREATE OR REPLACE FUNCTION "getUser"(_handle character, _email character) RETURNS blog."user"
	LANGUAGE plpgsql STABLE STRICT SECURITY DEFINER
	AS $_$
		DECLARE
			u "blog"."user";
		BEGIN
			IF 0 = (SELECT COUNT(*) FROM "blog"."user" WHERE handle = $1 AND email = $2) THEN
				RETURN null;
			END IF;
			SELECT * FROM "blog"."user" WHERE handle = $1 AND email = $2 INTO u;
			RETURN u;
		END;
	$_$;

CREATE OR REPLACE FUNCTION "publishPost"(_post_id integer) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		UPDATE "blog"."post" SET status = 'published'::status WHERE post_id = $1;
		SELECT $1;
	$_$;

CREATE OR REPLACE FUNCTION "search"(keywords text, page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER COST 150 ROWS 10
	AS $_$
		SELECT
			"blog"."published_posts".*
		FROM
			"blog"."published_posts" JOIN "blog"."blob"
				ON "blog"."published_posts".id = "blog"."blob".post_id,
			to_tsquery('english', $1) query
		WHERE
			query @@ search_data
		ORDER BY
			ts_rank_cd(search_data, query) DESC, "timestamp" DESC
		OFFSET
			(10 * $2)
		LIMIT 10;
	$_$;

CREATE OR REPLACE FUNCTION "setPassword"(handle text, password text) RETURNS void
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		UPDATE "blog"."user" SET pass = "blog"."hashPassword"($1, $2) WHERE handle = $1;
	$_$;

CREATE OR REPLACE FUNCTION "tagPost"(_post_id integer, _tags integer[]) RETURNS void
	LANGUAGE plpgsql STRICT SECURITY DEFINER
	AS $_$
		DECLARE
			x integer;
		BEGIN
			FOR x IN array_lower($2, 1) .. array_upper($2, 1) LOOP
				INSERT INTO "blog"."post_tags" (post_id, tag_id) VALUES ($1, $2[x]);
			END LOOP;

			DELETE FROM "blog"."post_tags" WHERE post_id = $1 AND NOT tag_id = any($2);
		END;
	$_$;

CREATE OR REPLACE FUNCTION "updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) RETURNS timestamp without time zone
	LANGUAGE plpgsql SECURITY DEFINER
	AS $_$
		DECLARE
		  t TIMESTAMP;
		  g CHARACTER VARYING(96);
		  s tsvector = NULL;
		BEGIN
		  IF ($4 IS NULL) THEN
			SELECT NOW() INTO t;
		  ELSE
			SELECT $4 INTO t;
		  END IF;

		  IF ($3 IS NULL) THEN
			SELECT slug FROM "blog"."post" WHERE post_id = $1 INTO g;
		  ELSE
			SELECT $3 INTO g;
		  END IF;

		  UPDATE "blog"."post" SET
			title     = $2,
			slug      = g,
			timestamp = t
		  WHERE post_id = $1;

		  IF ($5 IS NOT NULL) THEN
			SELECT to_tsvector('english', $2 || coalesce("blog"."stripTags"($5), '')) INTO s;
		  END IF;

		  UPDATE "blog"."blob" SET
			post_data   = $5,
			search_data = s
		  WHERE post_id = $1;

		  RETURN t;
		END;
	$_$;

-- Ownership of 'blog' schema
ALTER DATABASE blog OWNER TO blog;
ALTER SCHEMA public OWNER TO blog;
ALTER SCHEMA blog   OWNER TO blog;

ALTER TYPE   blog.status OWNER TO blog;

ALTER FUNCTION blog."CREATE OR REPLACEBlob"()   OWNER TO blog;
ALTER FUNCTION blog."hashPassword"(handle text, password text) OWNER TO blog;
ALTER FUNCTION blog."stripTags"(text)    OWNER TO blog;

ALTER TABLE blog."user"     OWNER TO blog;
ALTER TABLE blog."blob"     OWNER TO blog;
ALTER TABLE blog."post"     OWNER TO blog;
ALTER TABLE blog."tags"     OWNER TO blog;
ALTER TABLE blog.post_tags  OWNER TO blog;

ALTER TABLE blog.post_id_seq      OWNER TO blog;
ALTER TABLE blog.tag_id_seq       OWNER TO blog;
ALTER TABLE blog.user_id_seq      OWNER TO blog;

ALTER TABLE blog.all_tags         OWNER TO blog;
ALTER TABLE blog.all_posts        OWNER TO blog;
ALTER TABLE blog.published_posts  OWNER TO blog;

-- Ownership of 'public' schema
ALTER SCHEMA public OWNER TO blog;

ALTER FUNCTION public.authenticate(handle text, password text) OWNER TO blog;
ALTER FUNCTION public."countArchives"() OWNER TO blog;
ALTER FUNCTION public."CREATE OR REPLACEPost"(aid integer) OWNER TO blog;
ALTER FUNCTION public."CREATE OR REPLACEPost"(handle character varying) OWNER TO blog;
ALTER FUNCTION public."CREATE OR REPLACETag"(name text, slug text) OWNER TO blog;
ALTER FUNCTION public."CREATE OR REPLACEUser"(handle text, email text, pass text) OWNER TO blog;
ALTER FUNCTION public."getArchives"(page integer) OWNER TO blog;
ALTER FUNCTION public."getArchives"(tag_slug text, page integer) OWNER TO blog;
ALTER FUNCTION public."getPost"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getPost"(slug character varying) OWNER TO blog;
ALTER FUNCTION public."getPosts"() OWNER TO blog;
ALTER FUNCTION public."getTag"(_tag character) OWNER TO blog;
ALTER FUNCTION public."getTags"() OWNER TO blog;
ALTER FUNCTION public."getTags"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getUser"(_handle character, _email character) OWNER TO blog;
ALTER FUNCTION public."publishPost"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."search"(keywords text, page integer) OWNER TO blog;
ALTER FUNCTION public."setPassword"(handle text, password text) OWNER TO blog;
ALTER FUNCTION public."tagPost"(_post_id integer, _tags integer[]) OWNER TO blog;
ALTER FUNCTION public."updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) OWNER TO blog;

GRANT EXECUTE ON FUNCTION public.authenticate(handle text, password text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."countArchives"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."CREATE OR REPLACEPost"(aid integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."CREATE OR REPLACEPost"(handle character varying) TO "www-data";
GRANT EXECUTE ON FUNCTION public."CREATE OR REPLACETag"(name text, slug text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."CREATE OR REPLACEUser"(handle text, email text, pass text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getArchives"(page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getArchives"(tag_slug text, page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPost"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPost"(slug character varying) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPosts"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTag"(_tag character) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTags"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTags"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getUser"(_handle character, _email character) TO "www-data";
GRANT EXECUTE ON FUNCTION public."publishPost"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."search"(keywords text, page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."setPassword"(handle text, password text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."tagPost"(_post_id integer, _tags integer[]) TO "www-data";
GRANT EXECUTE ON FUNCTION public."updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) TO "www-data";

COMMIT;
