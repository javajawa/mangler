<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\Site,
	\Acorn\View;

class PostTeaser extends Renderer
{
	protected $post;

	public function __construct(\Mangler\Entity\Post $p, View $view)
	{
		parent::__construct($view);
		$this->post = $p;
	}

	public function doRender()
	{
		// Build the tag list
		$tags = array();
		foreach ($this->post->getTags() as $tag)
		{
			$tags[] = sprintf('<a href="%s">%s</a>', Site::getUri($tag), $tag->tag);
		}
		$tags = implode(', ', $tags) ?: 'None';

		// Get the link to the post
		$teaser      = $this->post->description();
		$link        = Site::getUri($this->post);

		return <<<EOF
<article>
	<h2><a href="{$link}">{$this->post->title}</a></h2>
	<div class="info">
		<span class="date">{$this->post->timestamp}</span>
		<span class="tags">Tags: {$tags} </span>
	</div>

{$this->indent($teaser, 1)}
	<a class="read-more" href="{$link}">Read More...</a>
</article>

EOF;
	}
}
