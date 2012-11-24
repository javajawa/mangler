<?php
namespace Mangler\Controller;

use \Acorn\Database\DatabaseException,
	\Mangler\Controller,
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

		$author = Database::getUser($handle, $email);

		if ($author->user_id === null)
		{
			try
			{
				$author = Database::createUser(array($handle, $email, null))->singleton();
			}
			catch (DatabaseException $ex)
			{
				$_SESSION['reply-flash'] = 'Username or email aready in use!<br />(Pick another, or a matched pair)';
				return $this->redirect(Site::getUri($post) . '?reply=' . $parent . '#reply', 307);
			}

			$author->user_id = $author->createUser;
		}

		$reply = Database::createReply($author->user_id, $parent);

		Database::updatePost($reply, $content);
		Database::submitComment($reply);

		$_SESSION['reply-flash'] = 'Your comment has been submitted';
		$this->redirect(Site::getUri($post).'#reply', 303);
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
