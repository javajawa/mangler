<?php
namespace Mangler\View;

use \Mangler\View,
	\Mangler\Renderer\PostTeaser;

class Post extends View
{
	protected $post;

	public function render()
	{
		$this->head();
		echo $this->post->render(2);
		$this->foot();
	}

	public function __construct(\Mangler\Entity\Post $post)
	{
		parent::__construct($post->title, $post->description());
		$this->post = new \Mangler\Renderer\Post($post, $this);
	}
}

