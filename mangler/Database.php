<?php
namespace Mangler;

class Database
{
	/**
	 *
	 * @var \Acorn\Database\Database
	 */
	private static $conn = null;

	public static function connect()
	{
		if (null === self::$conn)
		{
			self::$conn = new \Acorn\Database\Database('dbname=blog', '', '\Mangler\Entity');
		}
	}

	public static function connected()
	{
		return (null !== self::$conn);
	}

	private function __construct()
	{
		// Nothing to see here. Move along, citizen!
	}

	public static function getPost($id)
	{
		return self::$conn->storedProcedure('getPost', array($id), 'Post')->singleton();
	}

	public static function getRoot($id)
	{
		return self::$conn->storedProcedure('getRoot', array($id), 'Post')->singleton();
	}

	public static function getTags($post = null)
	{
		$param = ($post === null ? array() : array($post));
		return self::$conn->storedProcedure('getTags', $param, 'Tag');
	}

	public static function getTag($tag)
	{
		return self::$conn->storedProcedure('getTag', array($tag), 'Tag');
	}

	public static function addTag($post, $tag)
	{
		return self::$conn->storedProcedure('addTag', array($post, $tag));
	}

	public static function createPost($user)
	{
		$newPost = self::$conn->storedProcedure('createPost', array($user));
		return $newPost->singleton()->createPost;
	}
 
	public static function updatePost($id, $content, $title = '', $slug = null, $timestamp = null)
	{
		return self::$conn->storedProcedure('updatePost',
			array($id, $title, $slug, $timestamp, $content)
		)->singleton()->updatePost;
	}

	public static function publishPost($id)
	{
		return self::$conn->storedProcedure('publishPost', array($id))->singleton();
	}

	public static function deletePost($id)
	{
		return self::$conn->storedProcedure('deletePost', array($id));
	}

	public static function getAuthor($handle, $email)
	{
		return self::$conn->storedProcedure('getAuthor', array($handle, $email), 'User')->singleton();
	}

	public static function getArchives($page, $tag=null)
	{
		if (null === $tag)
		{
			return self::$conn->storedProcedure('getArchives', array($page), 'Post');
		}
		else
		{
			return self::$conn->storedProcedure('getArchives', array($tag, $page), 'Post');
		}
	}

	public static function countArchives()
	{
		$result = self::$conn->storedProcedure('countArchives', array());
		$result = $result->singleton();
		return $result->countArchives;
	}

	public static function getPosts()
	{
		return self::$conn->storedProcedure('getPosts', array(), 'Post');
	}

	public static function getUser($handle, $email = null)
	{
		$args = array($handle);
		if (false === empty($email))
		{
			$args [] = $email;
		}

		return self::$conn->storedProcedure('getUser', $args, 'User')->singleton();
	}

	public static function begin()
	{
		self::$conn->begin();
	}

	public static function commit()
	{
		self::$conn->commit();
	}

	public static function rollback()
	{
		self::$conn->rollback();
	}
}

Database::connect();
