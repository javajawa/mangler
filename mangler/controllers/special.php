<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Time,
	\Mangler\View\Login,
	\Mangler\View\Page,
	\Mangler\View\NotFound,
	\Mangler\View\Teapot;

/**
 * <p>Controller for actions which don't fit anywhere else (errors, index)</p>
 * <p>Methods:</p>
 * <dl>
 * <dt>error</dt><dd>Performs basic error reporting
 */
class Special extends Controller
{
	public function __construct()
	{
		parent::__construct('text/html');
	}

	public function error()
	{
		$this->cacheTime = 0;
		$this->eTag = false;

		if (isset($this->params->code))
			$code = $this->params->code;
		else
			$code = 500;

		$this->responseCode = $code;
		$status = \Acorn\Acorn::HTTPStatusMessage($code);
		switch ($code)
		{
			case 404: $view = new NotFound(); break;
			case 418: $view = new Teapot(); break;
			default:
				$view = new Page($status, "<h2>Error code {$code} - {$status}</h2>");
		}
		$view->render();
	}

	public function login()
	{
		$view = new Login($this->query->continue, $this->post);
		$view->render();
	}

	public function tracking()
	{
		
	}
}
