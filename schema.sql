
CREATE DATABASE blog
  WITH OWNER = blog
       ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_GB.UTF-8'
       LC_CTYPE = 'en_GB.UTF-8'
       CONNECTION LIMIT = -1;

GRANT ALL ON DATABASE blog TO postgres;
GRANT ALL ON DATABASE blog TO blog;

CREATE PROCEDURAL LANGUAGE plpgsql;

BEGIN;

CREATE SCHEMA blog;

SET search_path = blog, pg_catalog;

-- Digest function from postgres-contrib
CREATE FUNCTION digest(text, text)  RETURNS bytea LANGUAGE c IMMUTABLE STRICT AS '$libdir/pgcrypto', 'pg_digest';
CREATE FUNCTION digest(bytea, text) RETURNS bytea LANGUAGE c IMMUTABLE STRICT AS '$libdir/pgcrypto', 'pg_digest';


CREATE TYPE status AS ENUM (
	'draft',
	'published',
	'spam',
	'moderate'
);

-- Trigger for creating associated blobs for posts/comments
CREATE FUNCTION "createBlob"() RETURNS trigger
	LANGUAGE plpgsql STRICT SECURITY DEFINER
	AS $$BEGIN
		INSERT INTO blob (post_id, post_data, search_data)
			VALUES (LASTVAL(), NULL, NULL);

		RETURN new;
	END;$$;

-- Trigger for setting the root property correctly on root objects
CREATE FUNCTION "setRoot"() RETURNS trigger
	LANGUAGE plpgsql
	AS $$BEGIN
		IF new.root IS NULL THEN
			new.root = new.post_id;
		END IF;

		RETURN new;
	END;$$;

-- Password hashing function
CREATE FUNCTION "hashPassword"(handle text, password text) RETURNS character
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$SELECT encode(digest('v7qZYgtBXEt4vOraV4p++OrZFXSwiJn3uvz11X7Pj9GE/tMuIelN4lWSINujWHIIYPbKjB27ANcnv75sMbaCdw==' || $1 || $2 || 'mangle', 'sha512'), 'hex')$_$;

-- Function for {x,ht}ml stripping tags from text
CREATE FUNCTION "stripTags"(text) RETURNS text
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
	root        integer,
	reply       integer,
	status      status                DEFAULT 'moderate'::status
);

CREATE SEQUENCE post_id_seq START WITH 1 INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;
ALTER SEQUENCE  post_id_seq OWNED BY post.post_id;

ALTER TABLE ONLY "post" ALTER COLUMN   post_id           SET DEFAULT nextval('post_id_seq'::regclass);
ALTER TABLE ONLY "post" ADD CONSTRAINT post_pk           PRIMARY KEY (post_id);
ALTER TABLE ONLY "post" ADD CONSTRAINT author            FOREIGN KEY (user_id) REFERENCES "user"(user_id);
ALTER TABLE ONLY "post" ADD CONSTRAINT "reply-reference" FOREIGN KEY (reply)   REFERENCES "post"(post_id);
ALTER TABLE ONLY "post" ADD CONSTRAINT "root-reference"  FOREIGN KEY (root)    REFERENCES "post"(post_id);

CREATE INDEX "post-idx-reply"     ON "post" USING btree (root, reply, "timestamp");
CREATE INDEX "post-idx-slug"      ON "post" USING btree (slug);
CREATE INDEX "post-idx-timestamp" ON "post" USING btree ("timestamp");
CREATE INDEX "post-idx-title"     ON "post" USING btree (title);
CREATE INDEX "post-idx-user"      ON "post" USING btree (user_id);
CREATE UNIQUE INDEX "slug-check"  ON "post" USING btree (slug) WHERE (post_id = root);

CREATE TRIGGER "create-post-blob" AFTER INSERT ON post FOR EACH ROW EXECUTE PROCEDURE "createBlob"();
CREATE TRIGGER "set-post-root"   BEFORE INSERT ON post FOR EACH ROW EXECUTE PROCEDURE "setRoot"();

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
ALTER TABLE ONLY "post_tags" ADD CONSTRAINT post         FOREIGN KEY (post_id) REFERENCES post(post_id);
ALTER TABLE ONLY "post_tags" ADD CONSTRAINT tag          FOREIGN KEY (tag_id)  REFERENCES tags(tag_id);

CREATE INDEX "tag-idx-tag-id" ON post_tags USING btree (tag_id);

-- View tracking table
CREATE TABLE "tracking" (
	sequence_id integer                NOT     NULL,
	session_id  character(32)          NOT     NULL,
	tracking_id character(64)          DEFAULT NULL,
	access      timestamp              NOT     NULL,
	unload      timestamp              DEFAULT NULL,
	path        character varying(62)  NOT     NULL,
	referer     character varying(256) NOT     NULL
);

ALTER TABLE ONLY "tracking" ADD CONSTRAINT tracking_pk PRIMARY KEY (sequence_id);

CREATE INDEX "tracking-idx-sessions" ON "tracking" USING btree (tracking_id, session_id, access, unload);
CREATE INDEX "tracking-idx-times"    ON "tracking" USING btree (access, unload);
CREATE INDEX "tracking-idx-pages"    ON "tracking" USING btree (path, access, unload);
-- TODO: Add index by referer hostname
CREATE INDEX "tracking-idx-viewtime" ON "tracking" USING btree (extract(epoch from unload - access));

CREATE SEQUENCE tracking_id_seq START WITH 1 INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;
ALTER SEQUENCE  tracking_id_seq OWNED BY tracking.sequence_id;
ALTER TABLE ONLY "tracking" ALTER COLUMN sequence_id SET DEFAULT nextval('tracking_id_seq'::regclass);

-- Published Posts View
CREATE VIEW "published_posts" AS
	SELECT
		"post".post_id AS id,
		"post"."timestamp",
		"post".title,
		"post".slug,
		"blob".post_data AS content,
		"user".handle AS "user",
		"user".email,
		(
			SELECT
				(count(*) - 1)
			FROM
				post AS comments
			WHERE
				comments.root = post.post_id
			AND comments.status = 'published'::status
		) AS commentcount
	FROM
		"post" NATURAL JOIN "blob" NATURAL JOIN "user"
	WHERE
		"post".status = 'published'::status
	AND "post".reply IS NULL
	AND "post".timestamp <= NOW()
	ORDER BY
		"post"."timestamp" DESC
;

-- All Comments View
CREATE VIEW "all_comments" AS
	SELECT
		"post".post_id AS id,
		"post"."timestamp",
		"post".title,
		"post".slug,
		"blob".post_data AS content,
		"user".handle AS "user",
		"user".email,
		"post".root,
		"post".reply,
		"post".status
	FROM
		"post" NATURAL JOIN "blob" NATURAL JOIN "user"
	WHERE
		"post".reply IS NOT NULL
	ORDER BY
		"post".root,
		"post".reply,
		"post"."timestamp" DESC
;

-- All Posts View
CREATE VIEW "all_posts" AS
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
	WHERE
		"post".reply IS NULL
	ORDER BY
		"post"."timestamp" DESC;

-- All Tags View
CREATE VIEW "all_tags" AS
	SELECT
		"tags".tag,
		"tags".tag_slug,
		COUNT("post_tags".post_id) AS itemcount
	FROM
		tags NATURAL JOIN post_tags
	GROUP BY
		tags.tag_id, tags.tag, tags.tag_slug
;

SET search_path = public, blog, pg_catalog;

CREATE FUNCTION authenticate(handle text, password text) RETURNS blog."user"
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."user"
			WHERE "handle" = $1
			AND "pass" = "blog"."hashPassword"($1, $2)
			LIMIT 1;
	$_$;

CREATE FUNCTION "beginTracking"(sid character, puri character, ruri character) RETURNS integer
	LANGUAGE sql VOLATILE SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."tracking" (session_id, access, path, referer) VALUES ($1, NOW(), $2, $3);

		SELECT LASTVAL()::integer;
	$_$;

CREATE FUNCTION "countArchives"() RETURNS bigint
	LANGUAGE sql STABLE SECURITY DEFINER
	AS $_$
		SELECT COUNT(*) FROM "blog"."published_posts"
	$_$;

CREATE FUNCTION "createPost"(aid integer) RETURNS integer
	LANGUAGE sql SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."post" (timestamp, user_id, status)
			VALUES (NOW(), $1, 'draft'::status);

		UPDATE "blog"."post" SET root = post_id WHERE post_id = LASTVAL();
		SELECT LASTVAL()::integer;
	$_$;

CREATE FUNCTION "createPost"(handle character varying) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		SELECT "createPost"(
			(SELECT user_id FROM "blog"."user" WHERE handle = $1)
		);
	$_$;

CREATE FUNCTION "createReply"(author integer, parent integer) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		INSERT INTO "blog"."post" (timestamp, user_id, status, root, reply)
			SELECT NOW(), $1, 'moderate'::status, root, $2
				FROM   "blog"."post"
				WHERE  "post"."post_id" = $2;

		SELECT LASTVAL()::integer AS id;
	$_$;

CREATE FUNCTION "createTag"(name text, slug text) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$INSERT INTO "blog"."tags" (tag, tag_slug) VALUES($1, $2) RETURNING tag_id;$_$;

CREATE FUNCTION "createUser"(handle text, email text, pass text) RETURNS integer
	LANGUAGE sql SECURITY DEFINER
	AS $_$INSERT INTO "blog"."user" (handle, email, pass)

VALUES ($1, $2, "blog"."hashPassword"($1 || $2, $3));

SELECT LASTVAL()::integer;$_$;

CREATE FUNCTION "getArchives"(page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER ROWS 10
	AS $_$SELECT * FROM "blog"."published_posts" OFFSET (10 * $1) LIMIT 10;$_$;

CREATE FUNCTION "getArchives"(tag_slug text, page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER COST 125 ROWS 10
	AS $_$SELECT "blog"."published_posts".* FROM "blog"."published_posts" LEFT JOIN ("blog"."post_tags" NATURAL JOIN "blog"."tags") ON "blog"."published_posts".id = "blog"."post_tags".post_id WHERE "tag_slug" = $1 ORDER BY timestamp DESC OFFSET (10 * $2) LIMIT 10;$_$;

CREATE FUNCTION "getComments"(_post_id integer) RETURNS SETOF blog.all_comments
	LANGUAGE sql STABLE STRICT SECURITY DEFINER ROWS 100
	AS $_$SELECT * FROM all_comments WHERE root = $1;$_$;

CREATE FUNCTION "getComments"(_post_id integer, stat blog.status) RETURNS SETOF blog.all_comments
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$SELECT * FROM all_comments WHERE root = $1 AND status = $2 ORDER BY reply ASC, timestamp ASC;$_$;

CREATE FUNCTION "getPost"(_post_slug character varying) RETURNS blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$SELECT * FROM "blog"."published_posts" WHERE slug = $1;$_$;

CREATE FUNCTION "getPost"(_post_id integer) RETURNS blog.all_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$SELECT * FROM "blog"."all_posts" WHERE id = $1;$_$;

CREATE FUNCTION "getPosts"() RETURNS SETOF blog.all_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."all_posts"
	$_$;

CREATE FUNCTION "getRoot"(_post_id integer) RETURNS blog.all_posts
	LANGUAGE sql IMMUTABLE STRICT
	AS $_$
		SELECT * FROM blog."all_posts" WHERE id = (SELECT root FROM post WHERE post_id = $1);
	$_$;

CREATE FUNCTION "getTag"(_tag character) RETURNS blog.all_tags
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$SELECT * FROM "blog"."all_tags" WHERE tag_slug = $1;$_$;

CREATE FUNCTION "getTags"() RETURNS SETOF blog.all_tags
	LANGUAGE sql STABLE SECURITY DEFINER
	AS $$SELECT * FROM all_tags;$$;

CREATE FUNCTION "getTags"(_post_id integer) RETURNS SETOF blog.tags
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT "blog"."tags".*
		FROM "blog"."post_tags" NATURAL JOIN "blog"."tags"
		WHERE post_id = $1;
	$_$;

CREATE FUNCTION "getUser"(_handle character) RETURNS blog."user"
	LANGUAGE sql STABLE STRICT SECURITY DEFINER
	AS $_$
		SELECT * FROM "blog"."user" WHERE handle = $1 LIMIT 1;
	$_$;

CREATE FUNCTION "getUser"(_handle character, _email character) RETURNS blog."user"
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

CREATE FUNCTION "publishPost"(_post_id integer) RETURNS integer
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		UPDATE "blog"."post" SET status = 'published'::status WHERE post_id = $1;
		SELECT root FROM "blog"."post" WHERE post_id = $1;
	$_$;

CREATE FUNCTION "search"(keywords text, page integer) RETURNS SETOF blog.published_posts
	LANGUAGE sql STABLE STRICT SECURITY DEFINER COST 150 ROWS 10
	AS $_$
		SELECT
			"blog"."published_posts".*
		FROM
			"blog"."published_posts" JOIN "blog"."blob"
				ON "blog"."published_posts".id = "blog"."blob".post_id
		WHERE
			search_data @@ to_tsquery('english', $1)
		ORDER BY
			"timestamp" DESC
		OFFSET
			(10 * $2)
		LIMIT 10;
	$_$;

CREATE FUNCTION "setPassword"(handle text, password text) RETURNS void
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		UPDATE "blog"."user" SET pass = "blog"."hashPassword"($1, $2) WHERE handle = $1;
	$_$;

CREATE FUNCTION "submitComment"(_post_id integer) RETURNS void
	LANGUAGE sql STRICT SECURITY DEFINER
	AS $_$
		UPDATE "blog"."post" SET status = 'moderate'::status WHERE post_id = $1;
	$_$;

CREATE FUNCTION "tagPost"(_post_id integer, _tags integer[]) RETURNS void
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

CREATE FUNCTION "updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) RETURNS timestamp without time zone
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

ALTER FUNCTION blog."createBlob"()   OWNER TO blog;
ALTER FUNCTION blog."hashPassword"(handle text, password text) OWNER TO blog;
ALTER FUNCTION blog."setRoot"()      OWNER TO blog;
ALTER FUNCTION blog."stripTags"(text)    OWNER TO blog;

ALTER TABLE blog."user"     OWNER TO blog;
ALTER TABLE blog."blob"     OWNER TO blog;
ALTER TABLE blog."post"     OWNER TO blog;
ALTER TABLE blog."tags"     OWNER TO blog;
ALTER TABLE blog.post_tags  OWNER TO blog;
ALTER TABLE blog."tracking" OWNER TO blog;

ALTER TABLE blog.post_id_seq      OWNER TO blog;
ALTER TABLE blog.tag_id_seq       OWNER TO blog;
ALTER TABLE blog.user_id_seq      OWNER TO blog;
ALTER TABLE blog.tracking_id_seq  OWNER TO blog;

ALTER TABLE blog.all_comments     OWNER TO blog;
ALTER TABLE blog.all_tags         OWNER TO blog;
ALTER TABLE blog.all_posts        OWNER TO blog;
ALTER TABLE blog.published_posts  OWNER TO blog;

-- Ownership of 'public' schema
ALTER SCHEMA public OWNER TO blog;

ALTER FUNCTION public.authenticate(handle text, password text) OWNER TO blog;
ALTER FUNCTION public."beginTracking"(sid character, puri character, ruri character) OWNER TO blog;
ALTER FUNCTION public."countArchives"() OWNER TO blog;
ALTER FUNCTION public."createPost"(aid integer) OWNER TO blog;
ALTER FUNCTION public."createPost"(handle character varying) OWNER TO blog;
ALTER FUNCTION public."createReply"(author integer, parent integer) OWNER TO blog;
ALTER FUNCTION public."createTag"(name text, slug text) OWNER TO blog;
ALTER FUNCTION public."createUser"(handle text, email text, pass text) OWNER TO blog;
ALTER FUNCTION public."getArchives"(page integer) OWNER TO blog;
ALTER FUNCTION public."getArchives"(tag_slug text, page integer) OWNER TO blog;
ALTER FUNCTION public."getComments"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getComments"(_post_id integer, stat blog.status) OWNER TO blog;
ALTER FUNCTION public."getPost"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getPost"(slug character varying) OWNER TO blog;
ALTER FUNCTION public."getPosts"() OWNER TO blog;
ALTER FUNCTION public."getRoot"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getTag"(_tag character) OWNER TO blog;
ALTER FUNCTION public."getTags"() OWNER TO blog;
ALTER FUNCTION public."getTags"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."getUser"(_handle character, _email character) OWNER TO blog;
ALTER FUNCTION public."publishPost"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."search"(keywords text, page integer) OWNER TO blog;
ALTER FUNCTION public."setPassword"(handle text, password text) OWNER TO blog;
ALTER FUNCTION public."submitComment"(_post_id integer) OWNER TO blog;
ALTER FUNCTION public."tagPost"(_post_id integer, _tags integer[]) OWNER TO blog;
ALTER FUNCTION public."updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) OWNER TO blog;

GRANT EXECUTE ON FUNCTION public.authenticate(handle text, password text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."beginTracking"(sid character, puri character, ruri character) TO "www-data";
GRANT EXECUTE ON FUNCTION public."countArchives"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."createPost"(aid integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."createPost"(handle character varying) TO "www-data";
GRANT EXECUTE ON FUNCTION public."createReply"(author integer, parent integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."createTag"(name text, slug text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."createUser"(handle text, email text, pass text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getArchives"(page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getArchives"(tag_slug text, page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getComments"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getComments"(_post_id integer, stat "www-data".status) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPost"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPost"(slug character varying) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getPosts"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."getRoot"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTag"(_tag character) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTags"() TO "www-data";
GRANT EXECUTE ON FUNCTION public."getTags"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."getUser"(_handle character, _email character) TO "www-data";
GRANT EXECUTE ON FUNCTION public."publishPost"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."search"(keywords text, page integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."setPassword"(handle text, password text) TO "www-data";
GRANT EXECUTE ON FUNCTION public."submitComment"(_post_id integer) TO "www-data";
GRANT EXECUTE ON FUNCTION public."tagPost"(_post_id integer, _tags integer[]) TO "www-data";
GRANT EXECUTE ON FUNCTION public."updatePost"(_post_id integer, _title text, _slug text, utime timestamp without time zone, _content text) TO "www-data";

COMMIT;
