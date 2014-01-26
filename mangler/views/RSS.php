<?php
namespace Mangler\View;

use \Mangler\View,
	\Mangler\Renderer\RSSItem,
	\Acorn\Syndicatable,
	\Acorn\Request;

class RSS extends View
{
	protected $url;
	protected $description;
	protected $full;
	protected $image;
	protected $items     = array();
	protected $updated   = 0;
	protected $published = 0;

	public function __construct($items, $title, $url, $image, $description, $full = false)
	{
		parent::__construct($title);

		$this->full  = $full;
		$this->url   = $this->getUri($url);
		$this->image = $this->getUri($image);
		$this->description = $description;

		foreach ($items as $item)
		{
			if ($item instanceof Syndicatable)
			{
				$this->published = max($this->published, $item->published());
				$this->updated   = max($this->updated  , $item->lastUpdated());

				$this->items [] = new RSSItem($item, $this);
			}
		}

		$this->published = date('r', $this->published);
		$this->updated   = date('r', $this->updated);
	}

	public function isFull()
	{
		return $this->full;
	}

	public function render()
	{
		$this->head();
		foreach ($this->items as $item)
		{
			echo $item->render(2);
		}
		$this->foot();
	}

	protected function head()
	{
		echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>{$this->title}</title>
		<description>{$this->description}</description>
		<image>
			<title>{$this->title}</title>
			<url>{$this->image}</url>
			<link>{$this->url}</link>
		</image>
		<link>{$this->url}</link>
		<atom:link rel="self" href="{$this->getUri(Request::url())}" type="application/rss+xml" />
		<lastBuildDate>{$this->updated}</lastBuildDate>
		<pubDate>{$this->published}</pubDate>

EOF;
	}

	protected function foot($prev = null, $next = null, $ptext = 'Previous', $ntext = 'Next')
	{
		$prev = $next = $ptext = $ntext = null;
		echo <<<EOF
	</channel>
</rss>

EOF;
	}
}

