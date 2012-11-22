<?php
namespace Mangler;

class Database
{
	private static $conn = null;

	public static function connect()
	{
		if (null === self::$conn)
			self::$conn = new \Acorn\Database\Database('dbname=blog', '', '\Mangler\Entity');
	}

	public static function __callStatic($name, $arguments)
	{
		if (null === self::$conn)
			self::connect();

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

	public static function publishPost($id)
	{
		return self::__callStatic('publishPost',
			array(array($id), 'Post')
		)->singleton();
	}

	public static function getAuthor($handle, $email)
	{
		return self::__callStatic('getAuthor',
			array(array($handle, $email), 'User')
		)->singleton();
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
		return self::__callStatic('getPosts', array(array(), 'Post'));
	}

	public static function getUser($handle, $email = null)
	{
		$args = array($handle);
		if (false === empty($email))
			$args []= $email;

		return self::__callStatic('getUser', array($args, 'User'));
	}
}

