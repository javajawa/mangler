<?php
namespace Mangler\View;

use \Mangler\View;

class Page extends View
{
	protected $text;

	public function __construct($title, $text)
	{
		parent::__construct($title);
		$this->text = $text;
	}

	public function render()
	{
		$this->head();
		echo $this->text;
		$this->foot();
	}
}
