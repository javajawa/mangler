<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\Site;

class TagPicker extends Renderer
{
	protected $allTags;
	protected $tags;
	protected $post;

	public function __construct($post, $tags, $allTags, $view)
	{
		parent::__construct($view);

		$this->allTags = $allTags;
		$this->tags = $tags;
		$this->post = $post;
	}

	protected function doRender()
	{
		echo '<h2>Tags</h2><h3>Current</h3><ul>';
		foreach ($this->tags as $tag)
			echo '<li><a href="' . Site::getUri('/admin/tag/' . $this->post->id . '?tag=' . $tag->tag_id) . '">'. $tag->tag . '</a></li>';
		echo '</ul><h3>Available</h3><ul class="columns">';
		foreach ($this->allTags as $tag)
			echo '<li><a href="' . Site::getUri('/admin/tag/' . $this->post->id . '?tag=' . $tag->tag_id) . '">'. $tag->tag . '</a></li>';
		echo '</ul>';
	}
}
