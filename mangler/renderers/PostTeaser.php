<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\Site,
	\Acorn\View,
	\Acorn\TagParser\TagParser,
	\Mangler\Footnotes;

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
		// Generate the Teaser
		$matches = array();
		$teaser = '';
		preg_match_all(':(<p>.+?</p>):', $this->post->content, $matches);

		foreach ($matches[0] as $para)
		{
			$teaser .= $para . PHP_EOL;
			if (strlen($teaser) > 500)
				break;
		}
		Footnotes::reset($this->post->slug);
		$teaser = TagParser::strip($teaser);
		$teaser = wordwrap($teaser);

		// Build the tag list
		$tags = array();
		foreach ($this->post->getTags() as $tag)
			$tags[] = sprintf('<a href="%s">%s</a>', Site::getUri($tag), $tag->tag);

		$tags = implode(",\n\t\t\t", $tags);

		// Get the link to the post
		$link = Site::getUri($this->post);
		$commentWord = (1 === (int)$this->post->commentcount ? 'Comment' : 'Comments');

		return <<<EOF
<article>
	<h2><a href="{$link}">{$this->post->title}</a></h2>
	<div class="info">
		<span class="date">{$this->post->timestamp}</span>
		<a href="{$link}#comments" class="comments">{$this->post->commentcount} {$commentWord}</a>
	</div>

{$this->indent($teaser, 1)}
	<a class="read-more" href="{$link}">Read More...</a>
	<div class="info">
		<span class="tags">Tags:
			{$tags}
		</span>
		<a href="{$link}#comments" class="comments">{$this->post->commentcount} {$commentWord}</a>
	</div>
</article>

EOF;
	}
}
