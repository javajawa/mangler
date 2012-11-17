<?php
namespace Mangler\Entity;

use \Acorn\Entity,
	\Acorn\Syndicatable,
	\Mangler\Database,
	\Mangler\Renderer\PostTeaser;

class Post extends Comment implements Syndicatable
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

		$this->parseTags($this->content);
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

	public function published()
	{
		return $this->timestamp;
	}

	public function lastUpdated()
	{
		return $this->timestamp;
	}

	public function title()
	{
		return html_entity_decode($this->title, ENT_QUOTES, 'UTF-8');
	}

	public function authorName()
	{
		return sprintf('%s (%s)', $this->email, $this->user);
	}

	public function description($view)
	{
		$p = new PostTeaser($this, $view);
		return $p->render(0);
	}

	public function content($view)
	{
		return $this->content;
	}
}

