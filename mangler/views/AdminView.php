<?php
namespace Mangler\View;

use \Acorn\Renderer;

class AdminView extends \Acorn\View
{
	protected $title;
	protected $sections;

	public function __construct($title = null)
	{
		$this->title = $title;
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
		$title = (null !== $this->title ? $this->title . ' « ' : '') . 'Admin « '. $this->blogTitle;

		echo <<<EOF
<!DOCTYPE html>
<html id="top">
	<head>
		<meta charset="UTF-8" />
		<title>{$title}</title>
		<link rel="stylesheet" type="text/css" href="{$this->getUri('/resources/style')}" />
		<link href="http://fonts.googleapis.com/css?family=Marcellus+SC|Nunito:300" rel="stylesheet" type="text/css" />
		<link rel="alternate" href="{$this->getUri('/feed')}" type="application/rss+xml" />
		<link rel="icon" href="{$this->getUri('/resources/img/bug.png')}" type="image/png" />
	</head>
	<body>
		&nbsp;
		<header>
			<h1><a href="{$this->getUri('/')}">{$this->blogTitle}</a> &mdash; Admin</h1>
			<div id="tagline">{$this->blogTagLine}</div>
		</header>
		<div id="sidebar">
			<ul>
				<li><a href="{$this->getUri('/admin')}">Admin Home</a>
			</ul>
		</div>

EOF;
	}

	protected function foot()
	{
		echo <<<EOF
	</body>
</html>

EOF;
	}

	public function add(Renderer $s)
	{
		$this->sections []= $s;
	}

	public function render()
	{
		echo $this->head();
		foreach ($this->sections as $s)
			echo $s->render();
		echo $this->foot();
	}
}
