<?php
namespace Mangler\Renderer;

use \Mangler\Site,
	\Mangler\Entity\Comment,
	\Mangler\Entity\Post,
	\Mangler\Renderer\Row,
	\Acorn\View;

class PostInfo extends Row
{
	function __construct(Comment $post, View $view)
	{
		parent::__construct($view);
		$this->items []= sprintf('<a href="%s">%s</a>', Site::getUri('/admin/edit/' . $post->id), $post->title);
		$this->items []= $post->timestamp;
		$this->items []= sprintf('<a href="%s">Preview</a>', Site::getUri('/admin/preview/' . $post->id));
		$this->items []= sprintf('<a href="%s">Publish</a>', Site::getUri('/admin/publish/' . $post->id));
		$this->items []= $post->status;
	}
}
