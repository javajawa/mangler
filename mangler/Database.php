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
			self::$conn = new \Acorn\Database\Database('dbname=blog', '', '\Mangler\Entity');
	}

	public static function __callStatic($name, $arguments)
	{
		return self::$conn->__call($name, $arguments);
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
		return self::__callStatic('getPost', array(array($id), 'Post'))->singleton();
	}

	public static function getRoot($id)
	{
		return self::__callStatic('getRoot', array(array($id), 'Post'))->singleton();
	}

	public static function createReply($author, $parent)
	{
		return self::__callStatic('createReply',
			array(array($author, $parent))
		)->singleton()->createReply;
	}

	public static function updatePost($id, $content, $title = '', $slug = null, $timestamp = null)
	{
		return self::__callStatic('updatePost',
			array(array($id, $title, $slug, $timestamp, $content))
		)->singleton()->updatePost;
	}

	public static function submitComment($id)
	{
		return self::$conn->storedProcedure('submitComment', array($id))->singleton();
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
			return self::__callStatic('getArchives', array(array($page), 'Post'));
		else
			return self::__callStatic('getArchives', array(
				array($tag, $page), 'Post'));
	}

	public static function countArchives()
	{
		$result = self::__callStatic('countArchives', array());
		$result = $result->singleton();
		return $result->countArchives;
	}

	public static function getPosts()
	{
		return self::$conn->storedProcedure('getPosts', array(), 'Post');
	}

	public static function getComments()
	{
		return self::$conn->storedProcedure('getComments', array(), 'Comment');
	}

	public static function getUser($handle, $email = null)
	{
		$args = array($handle);
		if (false === empty($email))
			$args []= $email;

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
