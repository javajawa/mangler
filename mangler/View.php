<?php
namespace Mangler;

abstract class View extends \Acorn\View
{
	protected $title;
	protected $description = null;
	protected $blogTitle = Site::title;
	protected $blogTagLine = Site::tagline;

	public function __construct($title, $description = null)
	{
		$this->title = $title;
		$this->description = $description;
	}

	public function getUri($target)
	{
		if ($target instanceof \Acorn\Entity)
			return Site::getUri($target);
		else
		{
			$src = WWW_PATH;
			if ('/' === substr($src, -1))
				$src = substr($src, 0, strlen($src) - 1);

			return 'http://' . $src . $target;
		}
	}

	public function getAvatarUri($email)
	{
		return 'http://' . WWW_PATH . '/avatar/' . md5(strtolower($email)) . '/50';
	}

	protected function head()
	{
		$title = (null !== $this->title ? $this->title . ' « ' : '') . $this->blogTitle;
		$tags = '';
		$tags = $this->getTags();

		$description = htmlentities(
			strip_tags(str_replace(
				"\n",
				' ',
				$this->description ?: Site::title . ' '. Site::tagline
			))
		);
		$url = 'http://' . WWW_PATH . \Acorn\Request::url();

		echo <<<EOF
<!DOCTYPE html>
<html id="top">
	<head>
		<meta charset="UTF-8" />
		<title>{$title}</title>
		<link rel="stylesheet" type="text/css" href="{$this->getUri('/resources/style')}" />
		<link rel="alternate" href="{$this->getUri('/feed')}" type="application/rss+xml" />
		<link rel="icon" href="{$this->getUri('/resources/img/bug.png')}" type="image/png" />
		<!--[if lt IE 9]>
			<script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<meta property="og:title" content="{$title}" />
		<meta property="og:url" content="{$url}" />
		<meta property="og:description" content="{$description}" />
		<meta property="og:type" content="website" />
		<meta property="fb:profile_id" content="1040866428" />
	</head>
	<body>
		&nbsp;
		<header>
			<h1><a href="{$this->getUri('/')}">{$this->blogTitle}</a></h1>
			<div id="tagline">{$this->blogTagLine}</div>
		</header>
		<div id="sidebar">
			<h3><a href="{$this->getUri('/')}">Home</a></h3>
			<h3>Search</h3>
			<form id="searchbox" action="{$this->getUri('/search')}" method="get">
				<input name="s" value="" placeholder="Blog search" />
				<input type="image" src="{$this->getUri('/resources/img/searchbox.png')}" alt="search" />
			</form>

			<h3>Feeds</h3>
			<a id="feed" rel="alternate" href="{$this->getUri('/feed')}">RSS</a>

			<h3>Tags</h3>
			<ul>{$tags}
			</ul>

			<h3>Other</h3>
			<a id="about" href="{$this->getUri('/sticky/about')}">About The Blog</a>
			<a id="cookies" href="{$this->getUri('/sticky/cookies')}">Cookie Policy</a>
			<a id="theme" href="{$this->getUri('/sticky/theme')}">Theme Credits</a>
		</div>

EOF;
	}

	protected function foot($prev = null, $next = null, $ptext = 'Previous', $ntext = 'Next')
	{
		if (null !== $prev)
			$prev = sprintf('<a href="%s" rel="previous">%s</a>', $prev, $ptext);
		else
			$prev = '';

		if (null !== $next)
			$next = sprintf('<a href="%s" rel="next">%s</a>', $next, $ntext);
		else
			$next = '';

		echo <<<EOF
		<footer>
			<nav>
				{$prev}
				<a class="gotop" href="#top">Top</a>
				{$next}
			</nav>
		</footer>
	</body>
</html>

EOF;
	}

	private function getTags()
	{
		if (false === Database::connected())
			return '';

		$tags = '';
		foreach (Database::getTags(array(), 'Tag') as $tag)
			if ($tag->itemcount > 0)
				$tags .= "\n\t\t\t\t<li><a href=\"{$this->getUri($tag)}\">{$tag->tag} ({$tag->itemcount})</a></li>";

		return $tags;
	}
}
