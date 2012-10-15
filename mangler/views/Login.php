<?php
namespace Mangler\View;

use \Mangler\View;

class Login extends View
{
	protected $data;
	protected $forward;

	public function __construct($forward, $data)
	{
		parent::__construct('Account Control');
		$this->forward = $forward;
		$this->data    = $data;
	}

	public function render()
	{
		$this->head();

		$this->foot();
	}
}
