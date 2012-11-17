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
			$description = $this->item->content($this->view);
		else
			$description = $this->item->description($this->view);

		return <<<EOF
<item>
	<title><![CDATA[{$this->item->title()}]]></title>
	<author>{$this->item->authorName()}</author>
	<pubDate>{$pubDate}</pubDate>
	<guid>{$this->view->getUri($this->item)}</guid>
	<description>
	<![CDATA[
		{$description}
	]]>
	</description>
</item>

EOF;
	}

}

