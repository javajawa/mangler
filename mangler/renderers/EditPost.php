<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Acorn\View,
	\Mangler\Entity\Post;

class EditPost extends Renderer
{
	protected $post;

	public function __construct(Post $post, View $view)
	{
		parent::__construct($view);
		$this->post = $post;
	}

	protected function doRender()
	{
		return <<<EOF
<form method="post">
	Title: <input name="title" placeholder="Post Title" value="{$this->post->title}" />
	Slug: <input name="slug"  placeholder="post slub"  value="{$this->post->slug}"  />
	Publish: <input name="time"  placeholder="Publish"    value="{$this->post->timestamp}" />
	<textarea name="content" placeholder="Content!" style="min-height: 400px;" />{$this->post->content}</textarea>
	<input type="submit" value="Update" />
</form>
<hr />
<article style="border: 1px black solid; margin: 5px -5px; padding: 5px;">
	{$this->post->content()}
</article>
<hr />
<article style="border: 1px black solid; margin: 5px -5px; padding: 5px;">
	{$this->post->description()}
</article>
EOF;
	}
}

