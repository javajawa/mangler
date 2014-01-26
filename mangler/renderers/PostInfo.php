<?php
namespace Mangler\Renderer;

use \Mangler\Site,
	\Mangler\Entity\Post,
	\Mangler\Renderer\Row,
	\Acorn\View;

class PostInfo extends Row
{
	function __construct(Post $post, View $view)
	{
		parent::__construct($view);

		$this->items [] = sprintf('<a href="%s">%s</a>', Site::getUri('/admin/edit/' . $post->id), $post->title);
		$this->items []= $post->timestamp;

		switch ($post->status)
		{
			case 'published':
				$this->items []= sprintf('<a href="%s">View</a>', Site::getUri($post));
				break;
			case 'draft':
				$this->items []= sprintf('<a href="%s">Publish</a>', Site::getUri('/admin/publish/' . $post->id));
				break;
		}
		$this->items []= sprintf('<a href="%s">Edit</a>', Site::getUri('/admin/edit/' . $post->id));
		$this->items []= sprintf('<a href="%s">Tags (%d)</a>', Site::getUri('/admin/tag/' . $post->id), count($post->getTags()));
		$this->items []= sprintf('<a href="%s">Delete</a>',  Site::getUri('/admin/delete/'  . $post->id));
	}
}
