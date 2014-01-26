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
		$posts = Database::getArchives($page);
		$count = (int)Database::countArchives();
		$count = (int)floor($count / 10);

		$view  = new \Mangler\View\Archive($page, $count, $posts);
		$view->render();
	}

	public function tag()
	{
		$tag   = $this->params->tag;
		$page  = (int)$this->params->page;
		$posts = Database::getArchives($page, $tag);

		$tag   = Database::getTag($tag);
		$tag   = $tag->singleton();
		$count = $tag->itemcount;
		$count = (int)floor($count / 10);

		$view  = new \Mangler\View\Archive($page, $count, $posts, 'Posts tagged ' . $tag->tag);
		$view->render();
	}

}
