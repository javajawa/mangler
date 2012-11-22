<?php
namespace Mangler\Entity;

use \Acorn\Entity,
	\Acorn\Syndicatable,
	\Mangler\Database,
	\Mangler\Renderer\PostTeaser,
	\Mangler\Footnotes,
	\Acorn\TagParser\TagParser;

class Post extends Comment implements Syndicatable
{
	protected $slug;
	protected $commentcount;
	protected $comments;
	protected $tags;

	public static function create($title, $content)
	{
		$post = new Post();
		$post->slug = '';
		$post->title = $title;
		$post->content = $content;
		$post->user = 'Preview';
		$post->email = 'test@example.com';
		$post->id = 0;
		$post->tags = array();
		$post->comments = array();
		$post->commentcount = 0;

		return $post;
	}

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

	public function description()
	{
		$matches = array();
		$teaser = '';
		preg_match_all(':(<p>.+?</p>):smi', $this->content, $matches);
		Footnotes::reset($this->slug);

		foreach ($matches[0] as $para)
		{
			$teaser .= TagParser::strip($para) . PHP_EOL;
			if (strlen($teaser) > 300)
				break;
		}
		if (DEBUG)
			$teaser = wordwrap($teaser);

		return $teaser;
	}

	public function content()
	{
		Footnotes::reset($this->slug);
		return TagParser::parse($this->content . ' <lons />');
	}
}

