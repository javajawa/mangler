<?php
namespace Mangler\View;

use \Mangler\View,
	\Mangler\Renderer\PostTeaser;

class Post extends View
{
	protected $post;
	protected $form;
	protected $comments;

	public function render()
	{
		$this->head();
		echo $this->post->render(2);
		echo $this->comments->render(2);
		echo $this->form->render(2);
		$this->foot();
	}

	public function __construct(\Mangler\Entity\Post $post, $reply)
	{
		parent::__construct($post->title, $post->description());
		$this->post = new \Mangler\Renderer\Post($post, $this);
		$this->form = new \Mangler\Renderer\ReplyForm($reply, $this);
		$this->comments = new \Mangler\Renderer\CommentTree($post, $this);
	}
}

