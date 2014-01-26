<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Time,
	\Mangler\Database;

class Search extends Controller
{

	public function __construct()
	{
		parent::__construct('text/html');
	}

	public function before()
	{
		parent::before();
		Database::connect();
	}

	public function after()
	{
		$this->eTag = true;
		$this->cachePublic = true;
		$this->cacheTime = Time::HOUR;
		parent::after();
	}

	public function init()
	{
		if (true === empty($this->query->s))
		{
			$this->redirect('/', 301);
		}

		$this->redirect('/search/' . urlencode($this->query->s), 301);
	}

	public function search()
	{
		$query = str_replace(
			array('&', '|', '!', '+-', '+'),
			array('',  '',  '',  '|!', '|'),
			$this->params->query
		);

		$page  = (int)$this->params->page;
		$posts = Database::search(array($query, $page), '\Mangler\Entity\Post');

		$view  = new \Mangler\View\Archive($page, 1, $posts,
			sprintf('Search Results for "%s"', str_replace('+',' ', $this->params->query)));
		$view->render();
	}
}
