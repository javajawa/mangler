<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Database;

class Post extends Controller
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
		parent::after();
	}

	public function get()
	{
		$slug  = $this->params->slug;

		$post = Database::getPost($slug);

		if (null === $post->id)
		{
			$this->responseCode = 404;
			$view = new \Mangler\View\Page(RESOURCE_PATH . 'pages/404.html', 'Post Not Found');
		}
		else
		{
			$view  = new \Mangler\View\Post($post);
		}

		$view->render();
	}
}
