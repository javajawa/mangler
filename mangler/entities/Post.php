<?php
namespace Mangler\Entity;

use \Acorn\Syndicatable,
	\Mangler\Database,
	\Mangler\Footnotes,
	\Acorn\TagParser\TagParser;

class Post extends \Acorn\Entity implements Syndicatable
{
	protected $id;
	protected $timestamp;
	protected $title;
	protected $slug;
	protected $user;
	protected $email;
	protected $tags;
	protected $content;

	public function __get($name)
	{
		if ('timestamp' === $name)
		{
			return date('j M Y G:i', $this->timestamp);
		}
		return parent::__get($name);
	}

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
		$this->tags     = NULL;
		$this->timestamp = strtotime($this->timestamp);
	}

	public function getTags()
	{
		if (null == $this->tags)
		{
			$this->tags = Database::getTags($this->id);
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
			{
				break;
			}
		}
		if (DEBUG)
		{
			$teaser = wordwrap($teaser);
		}

		return $teaser;
	}

	public function content()
	{
		Footnotes::reset($this->slug);
		return TagParser::parse($this->content . ' <lons />');
	}
}

