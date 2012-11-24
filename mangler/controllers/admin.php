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
		$view = new AdminView();

		$posts = Database::getPosts();
		$postlist = new Table($view, 'Posts');

		foreach ($posts as $post)
			$postlist->add(new PostInfo($post, $view));

		$view->add($postlist);

		$posts = Database::getComments();
		$postlist = new Table($view, 'Comments');

		foreach ($posts as $post)
			$postlist->add(new PostInfo($post, $view));

		$view->add($postlist);
		$view->render();
	}

	public function create()
	{
		if (array_key_exists('REDIRECT_REMOTE_USER', $_SERVER))
			$_SERVER['REMOTE_USER'] = $_SERVER['REDIRECT_REMOTE_USER'];

		if (empty($this->post->title) || empty($this->post->slug))
		{
			$_SESSION['flash'] = 'A valid title and slug is required';
			return $this->redirect('/admin', 303);
		}

		Database::begin();
		try
		{
			$user    = $_SERVER['REMOTE_USER'];
			$user    = Database::getUser($user);
			if ($user === null)
				die;

			$newPost = Database::createPost(array($user->handle));
			$newPost = $newPost->singleton()->createPost;

			Database::updatePost($newPost, $this->post->content, $this->post->title, $this->post->slug);
			Database::commit();

			return $this->redirect('/admin/edit/' . $newPost);
		}
		catch (DatabaseException $ex)
		{
			Database::rollback();
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
				$this->post->content,
				$this->post->title,
				$this->post->slug,
				date('Y-m-d H:i:s', strtotime($this->post->time))
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
		$_SESSION['flash'] = 'Post has been published';
		$this->redirect($_SERVER['HTTP_REFERER']);
	}

	public function delete()
	{
		$id = $this->params->post;

		\Mangler\Database::deletePost($id);
		$_SESSION['flash'] = 'Post deleted successfully';
		$this->redirect('/admin', 303);
	}
}
