<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\View;

class CommentTree extends Renderer
{
	protected $mapping = array();
	protected $root;
	protected $count;

	public function __construct(\Mangler\Entity\Post $post, View $view)
	{
		parent::__construct($view);

		$this->root  = $post->id;
		$comments    = $post->getComments();
		$this->count = count($comments);

		foreach ($comments as $comment)
		{
			$this->mapping[$comment->reply] []= $comment;
		}
	}

	public function doRender()
	{
		$countString = (1 === $this->count) ? 'Reply' : 'Replies';

		return <<<EOF
<section id="comments">
	<h3>{$this->count} {$countString}</h3>
{$this->indent($this->renderChildren($this->root), 1)}
</section>

EOF;
	}

	public function renderChildren($parent)
	{
		if (false === array_key_exists($parent, $this->mapping)) return '';

		$children = $this->mapping[$parent];
		$result = '<div class="commentlist">' . PHP_EOL;
		foreach ($children as $child)
		{
			$result .= <<<EOF
	<div class="comment" id="comment-{$child->id}">
		<span class="info right">
			<span class="date">{$child->timestamp}</span>
			<a href="?reply={$child->id}#reply" class="reply">Reply</a>
		</span>
		<div class="comment-author">
			<img class="avatar" width="50" height="50" alt="{$child->user}"
				src="{$this->view->getAvatarUri($child->email)}" />
			{$child->user}
		</div>
		<h3>{$child->title}</h3>
		<p>
{$this->indent($child->content, 3)}
		</p>
		<div class="clear"></div>
{$this->indent($this->renderChildren($child->id), 2)}
		<div class="info">
			<a class="gotop" href="#comment-{$child->id}">Top of comment</a>
			<a href="?reply={$child->id}#reply" class="reply">Reply</a>
		</div>
	</div>

EOF;
		}
		return $result . PHP_EOL . '</div>';
	}
}
