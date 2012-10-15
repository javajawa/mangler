<?php
namespace Mangler\View;

use \Mangler\View;

class NotFound extends View
{
	public function __construct()
	{
		parent::__construct('Not Found');
	}

	public function render()
	{
		$this->head();
		echo <<<EOF
			<h2>Page Not Found</h2>
			<p class="error">The page you have requested can not be found.</p>
			<p>Have a kitten.</p>
			<img class="center" src="{$this->getUri('/resources/img/error.jpg')}" width="500" height="333" />

EOF;
		$this->foot();
	}
}

