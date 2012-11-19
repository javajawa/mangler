<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Time,
	\Mangler\Site,
	\Mangler\Database,
	\Mangler\View\NotFound;

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
		$this->track();
		parent::after();
	}

	public function get()
	{
		$slug  = $this->params->slug;

		$post = Database::getPost($slug);

		if (null === $post->id)
		{
			$this->responseCode = 404;
			$view = new NotFound();
		}
		else
		{
			if (true === empty($this->query->reply))
				$reply = $post->id;
			else
				$reply = $this->query->reply;

			$view  = new \Mangler\View\Post($post, $reply);
		}

		$view->render();
	}

	public function reply()
	{
		if (true === empty($this->params->parent))
			$this->redirect('/', 303);

		$parent  = (int)$this->params->parent;
		$post    = Database::getRoot($parent);

		$content = $this->postData('content');
		$handle  = $this->postData('handle');
		$email   = $this->postData('email');


		if (false === isset($content, $handle, $email))
		{
			$_SESSION['reply-flash'] = 'All fields are required';
			return $this->redirect(Site::getUri($post) . '?reply=' . $parent . '#reply', 307);
		}

		$_SESSION['reply-flash'] = 'Commenting is currently disabled';
		return $this->redirect(Site::getUri($post) . '?reply=' . $parent . '#reply', 307);
		/*$reply = Database::createReply($author, $parent);

		Database::updatePost($post_id, $this->post->content);
		Database::publishPost(array($post_id));

		$this->redirect(Site::getUri($post), 303);*/
	}

	private function postData($key)
	{
		if (false === isset($this->post->$key))
			return null;

		$ret = trim($this->post->$key);
		if (true  === empty($this->post->$key))
			return null;

		return $ret;
	}
}
