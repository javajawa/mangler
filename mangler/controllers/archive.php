<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Database;

class Archive extends Controller
{

	public function __construct()
	{
		parent::__construct('text/html');
	}

	public function after()
	{
		$this->eTag = true;
		parent::after();
	}

	public function time()
	{
		$page  = (int)$this->params->page;
		$posts = Database::getArchives(array($page), 'Post');

		$view  = new \Mangler\View\Archive($page, 1, $posts);
		$view->render();
	}

	public function tag()
	{
		$tag   = $this->params->tag;
		$page  = (int)$this->params->page;
		$posts = Database::getArchives(array($tag, $page), 'Post');

		$tag   = Database::getTag(array($tag), 'Tag');
		$tag   = $tag->singleton();

		$view  = new \Mangler\View\Archive($page, 1, $posts, 'Posts tagged ' . $tag->tag);
		$view->render();
	}
}
