<?php
namespace Mangler\View;

use \Mangler\View;

class Teapot extends View
{
	public function __construct()
	{
		parent::__construct('I\'m a Tea Pot');
	}

	public function render()
	{
		$this->head();
		echo <<<EOF
			<h2>Unable to Brew Coffee &mdash; I'm A Teapot (Error 418)</h2>
			<img class="space center" src="{$this->getUri('/resources/img/teapot.jpg')}" width="600" height="402" />
			<p>This deivce is unable to complete your
			<abbr title="Hyper-text coffee-pot control protocol">HTCPCP</abbr> request.
			For more information on the protcol, refer to
			<a href="http://tools.ietf.org/html/rfc2324" rel="nofollow">
			<abbr title="Request for comments">RFC</abbr> 2324</a>.
			Due to clear caffiene-source favouritism in the <abbr title="World Wide Web Consortium">W3C</abbr>
			and <abbr title="Internet Engineering Task Force">IETF</abbr>, no protocol has been defined for tea pots.</p>
			<p>This tea pot has been optimised for Tea, Earl Grey, Hot.</p>
			<p>Image from <a href="http://www.freefoto.com/preview/9907-04-8/Tea-Pot" rel="nofollow">FreeFoto.com</a></p>
EOF;
		$this->foot();
	}
}

