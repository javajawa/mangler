<?php
namespace Mangler\Entity;

use \Acorn\Entity,
	\Mangler\Database;

class Post extends Comment
{
	protected $slug;
	protected $commentcount;
	protected $comments;
	protected $tags;

	public function __construct()
	{
		parent::__construct();
		$this->comments = NULL;
		$this->tags     = NULL;
	}

	public function getComments()
	{
		if (null === $this->comments)
		{
			$this->comments = Database::getComments(array($this->id, 'published'), 'Comment');
		}
		return $this->comments;
	}

	public function getTags()
	{
		if (null == $this->tags)
		{
			$this->tags = Database::getTags(array($this->id), 'Tag');
		}
		return $this->tags;
	}
}

