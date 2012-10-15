<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\Site,
	\Mangler\View;

class Post extends Renderer
{
	protected $post;

	public function __construct(\Mangler\Entity\Post $post, View $view)
	{
		$this->post = $post;
		parent::__construct($view);
	}

	public function doRender()
	{
		$tags = array();
		foreach ($this->post->getTags() as $tag)
			$tags[] = sprintf('<a href="%s">%s</a>', Site::getUri($tag), $tag->tag);
		$tags = implode(', ', $tags);
		$commentWord = (1 === (int)$this->post->commentcount ? 'Comment' : 'Comments');

		$urlTitle = urlencode($this->post->title);
		$url = urlencode($this->view->getUri($this->post));

		return <<<EOF
<h2>{$this->post->title}</h2>
<div class="info">
	<span class="date">{$this->post->timestamp}</span>
	<a href="#comments" class="comments">{$this->post->commentcount} {$commentWord}</a>
</div>

{$this->post->content}

<div class="info">
	<span class="tags">Tags: {$tags}</span>
	<span class="comments">{$this->post->commentcount} Comments</span>
	<span>Share:
		<a href="http://facebook.com/sharer.php?u={$url}">Facebook</a>
		<a href="http://twitter.com/share?text={$urlTitle}&url={$url}">Twitter</a>
		<a href="http://plus.google.com/share?url={$url}">Google+</a>
	</span>
</div>

EOF;
	}
}
