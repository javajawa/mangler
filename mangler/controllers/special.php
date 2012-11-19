<?php
namespace Mangler\Controller;

use \Mangler\Controller,
	\Mangler\Time,
	\Mangler\View\Login,
	\Mangler\View\Page,
	\Mangler\Database,
	\Acorn\Acorn;

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
		$status = Acorn::HTTPStatusMessage($code);
		$file   = RESOURCE_PATH . 'pages/' . $code . '.html';

		if (!file_exists($file))
			$view = new Page("<h2>Error code {$code} - {$status}</h2>", $status);
		else
			$view = new Page($file);

		$view->render();
	}

	public function page()
	{
		Database::connect();
		$this->track();
		if (isset($this->params->name) AND file_exists(RESOURCE_PATH . 'pages/' . $this->params->name . '.html'))
		{
			$view = new Page(RESOURCE_PATH . 'pages/' . $this->params->name . '.html');
		}
		else
		{
			$this->responseCode = 404;
			$view = new Page(RESOURCE_PATH . 'pages/404.html');
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
