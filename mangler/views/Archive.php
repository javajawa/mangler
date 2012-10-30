<?php
namespace Mangler\View;

use \Mangler\View,
	\Mangler\Renderer\PostTeaser;

class Archive extends View
{
	protected $posts = array();
	protected $page;
	protected $max;

	public function __construct($page, $max, $posts, $title = null)
	{
		parent::__construct($title);
		$this->page  = $page;
		$this->max   = $max;

		foreach ($posts as $post)
			if ($post instanceof \Mangler\Entity\Post)
				$this->posts[] = new PostTeaser($post, $this);
	}

	public function render()
	{
		$this->head();

		$indent = str_repeat("\t", 4);
		$i = 0;

		if (null !== $this->title)
			printf('%s<h2>%s</h2>%s%s<hr />%s', $indent, $this->title, PHP_EOL, $indent, PHP_EOL);

		foreach ($this->posts as $post)
		{
			echo $post->render(2);

			if (count($this->posts) !== ++$i)
				printf('%s<hr />%s', $indent, PHP_EOL);
		}

		$prev = (0 !== $this->page) ?
			$this->getUri('/page/' . ($this->page - 1)) : null;
		$next = ($this->max !== $this->page) ?
			$this->getUri('/page/' . ($this->page + 1)) : null;

		$this->foot($prev, $next, 'Newer', 'Older');
	}

}

