<?php
namespace Mangler\View;

use \Acorn\Renderer,
	\Mangler\Site;

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
		return Site::getUri($target);
	}

	public function getAvatarUri($email)
	{
		return 'http://' . WWW_PATH . '/avatar/' . md5(strtolower($email)) . '/50';
	}

	protected function head()
	{
		$title = (null !== $this->title ? $this->title . ' « ' : '') . 'Admin « '. Site::title;

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
			<h1><a href="{$this->getUri('/')}">Blog</a> &mdash; Admin</h1>
		</header>
		<div id="sidebar">
			<ul>
				<li><a href="{$this->getUri('/admin')}">Admin Home</a></li>
			</ul>
			<h3>Create Post</h3>
			<form action="{$this->getUri('/admin/create')}" method="post">
				<input name="title" placeholder="Post Title" />
				<input name="slug" placeholder="post-slug" />
				<textarea name="content" placeholder="Content"></textarea>
				<input type="submit" value="Create" />
			</form>
		</div>

EOF;
		if (isset($_SESSION['flash']))
		{
			echo '<div class="flash center">' . $_SESSION['flash'] . '</div>';
			unset($_SESSION['flash']);
		}
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
