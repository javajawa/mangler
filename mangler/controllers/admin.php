<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Database,
	\Mangler\Entity\Post,
	\Mangler\Site,
	\Mangler\View\AdminView
	\Mangler\;

class Admin extends Controller
{

	public function __construct()
	{
		parent::__construct('text/html');
		$this->eTag = false;
	}

	public function before()
	{
		// TODO: digest authentication
	}

	public function index()
	{
		$posts = Database::getPosts();

		$view = new AdminView();
		$postlist = new PostList();

		foreach ($post as $post)
			$postlist->add(new PostInfo($post));

		$view->render();
	}

	public function create()
	{
		if (empty($this->post->title) || empty($this->post->slug))
			$this->redirect('/admin', 303);

		try
		{
			$newPost = Database::createPost();
			$newPost = $newPost->singleton();

			Database::updatePost(array(
				$newPost,
				$this->post->title,
				$this->post->slug,
				null,
				''
			));

			$this->redirect('/admin/edit/' . $newPost);
		}
		catch (DatabaseException $ex)
			goto error;

		if (null === $newPost)
			goto error;

error:
		$_SESION['flash'] = 'An unexpected error occured when attempting to create the post';
		$this->redirect('/admin', 303);
	}

	public function edit()
	{
		if (empty($this->params->post))
			$this->redirect('/admin', 303);

		if (isset($this->post->title))
			Database::updatePost(array(
				(int)$this->params->post,
				$this->post->title,
				$this->post->slug,
				$this->post->time,
				$this->post->content
			));

		$post = Database::getPost((int)$this->params->post)->singleton();
		if (null === $post)
			$this->redirect('/admin', 303);

		$view = new AdminView();
		$view->add(new EditPost($post));
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
		$view->add(new \Mangler\Renderer\Post($post));
		$view->render();
	}

	public function publish()
	{
		Database::publishPost(array((int)$this->params->post));
		$this->redriect('/admin/edit/' . $this->params->post);
	}
}

