<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Acorn\Syndicatable,
	\Mangler\View\RSS;

class RSSItem extends Renderer
{
	protected $item;

	public function __construct(Syndicatable $item, RSS $view)
	{
		parent::__construct($view);
		$this->item = $item;
	}

	public function doRender()
	{
		$pubDate = date('r', $this->item->published());

		if (true === $this->view->isFull())
		{
			$description = $this->item->content($this->view);
		}
		else
		{
			$description = $this->item->description($this->view);
		}

		$description = str_replace('href="/', 'href="https://' . $_SERVER['SERVER_NAME'] . '/', $description);

		$tags = '';
		foreach ($this->item->getTags() as $tag)
		{
			$tags .= "\t" . '<category>' . $tag->tag . '</category>' . PHP_EOL;
		}

		return <<<EOF
<item>
	<title><![CDATA[{$this->item->title()}]]></title>
	<author>{$this->item->authorName()}</author>
	<pubDate>{$pubDate}</pubDate>
	<guid>{$this->view->getUri($this->item)}</guid>
	<link>{$this->view->getUri($this->item)}</link>
	<description>
	<![CDATA[
		{$description}
	]]>
	</description>
{$tags}</item>

EOF;
	}

}

