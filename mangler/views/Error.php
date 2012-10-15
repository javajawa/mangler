<?php
namespace Mangler\View;

use \Mangler\View;

class Error extends View
{
	protected $errors;

	public function __construct(array $errors)
	{
		parent::__construct('Error');
		$this->errors = $errors;
	}

	public function render()
	{
		$this->head();

		foreach ($this->errors as $error)
			if ($error instanceof \Mangler\Renderer\Error)
				$error->render();

		$this->foot();
	}
}

