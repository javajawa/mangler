<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Database,
	\Acorn\Database\DatabaseException,
	\Mangler\Renderer\EditPost,
	\Mangler\Renderer\PostInfo,
	\Mangler\Renderer\Table,
	\Mangler\Site,
	\Mangler\View\AdminView;

class Admin extends Controller
{

	public function __construct()
	{
		parent::__construct('text/html');
		$this->eTag = false;
	}

	public function index()
	{
		$posts = Database::getPosts();

		$view = new AdminView();
		$postlist = new Table($view);

		foreach ($posts as $post)
			$postlist->add(new PostInfo($post, $view));

		$view->add($postlist);
		$view->render();
	}

	public function create()
	{
		if (empty($this->post->title) || empty($this->post->slug))
			$this->redirect('/admin', 303);

		try
		{
			$user    = $_SERVER['REDIRECT_REMOTE_USER'];
			$user    = Database::getUser($user)->singleton();
			if ($user === null)
				die;

			$newPost = Database::createPost(array($user->handle));
			$newPost = $newPost->singleton()->createPost;

			Database::updatePost($newPost, $this->post->content, $this->post->title, $this->post->slug);

			$this->redirect('/admin/edit/' . $newPost);
		}
		catch (DatabaseException $ex)
		{
			goto error;
			(object)$ex;
		}

		if (null === $newPost)
			goto error;

error:
		$_SESSION['flash'] = 'An unexpected error occured when attempting to create the post';
		$this->redirect('/admin', 303);
	}

	public function edit()
	{
		if (empty($this->params->post))
			$this->redirect('/admin', 303);

		if (isset($this->post->title))
			Database::updatePost(
				(int)$this->params->post,
				$this->post->title,
				$this->post->slug,
				$this->post->time,
				$this->post->content
			);

		$post = Database::getPost((int)$this->params->post);
		if (null === $post)
			$this->redirect('/admin', 303);

		$view = new AdminView();
		$view->add(new EditPost($post, $view));
		$view->render();
	}

	public function preview()
	{
		$id = (int)$this->params->post;
		if (empty($id))
		{
			if (empty($this->post->title) || empty($this->post->content))
			{
				$this->responseCode = 400; // Bad Request
				return;
			}
			$post = Post::create($this->post->title, $this->post->content);
		}
		else
		{
			$post = Database::getPost($id);
			if ($post === null)
			{
				$this->responseCode = 404; // Not Found
				return;
			}
		}

		$view = new AdminView();
		$view->add(new \Mangler\Renderer\Post($post, $view));
		$view->render();
	}

	public function publish()
	{
		Database::publishPost((int)$this->params->post);
		$_SESSION['flash'] = 'This post has been published';
		$this->redirect('/admin/edit/' . $this->params->post);
	}
}

