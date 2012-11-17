<?php
namespace Mangler\Controller;

use \Mangler\Controller, \Mangler\Time;

class Resources extends Controller
{

	public function __construct()
	{
		parent::__construct('', false);
	}

	public function style()
	{

		$files = glob(RESOURCE_PATH . 'css/*.css');

		$tag  = '';
		foreach ($files as $file)
			$tag .= `md5sum "{$file}"`;

		$this->eTag = md5($tag);
		$this->cachePublic = true;
		$this->cacheTime   = Time::LUNAR_MONTH;

		if ($this->ifMatch() && false)
			return;

		$this->resetBuffer('text/css');

		foreach ($files as $file)
		{
			echo '/* ' . $file . ' */' . PHP_EOL;
			$fh = fopen($file, 'rb');
			fpassthru($fh);
			fclose($fh);
		}
	}

	public function script()
	{
		$files = glob(RESOURCE_PATH . 'js/*.js');

		$tag  = '';
		foreach ($files as $file)
			$tag .= `md5sum "{$file}"`;

		$this->eTag = md5($tag);
		$this->cachePublic = true;
		$this->cacheTime   = Time::LUNAR_MONTH;

		if ($this->ifMatch() && false)
			return;

		$this->resetBuffer('text/javascript');

		foreach ($files as $file)
		{
			echo '/* ' . $file . ' */' . PHP_EOL;
			$fh = fopen($file, 'rb');
			fpassthru($fh);
			fclose($fh);
		}
	}

	public function image()
	{
		$file = $this->params->file;
		$file = RESOURCE_PATH . 'img/' . $file;

		if (false === file_exists($file))
		{
			$this->responseCode = 404;
			return;
		}

		$this->cachePublic = true;
		$this->cacheTime   = 2551443;
		$this->eTag = md5(`md5sum "{$file}"`);
		if ($this->ifMatch())
			return;

		$fp = fopen($file, 'rb');
		fpassthru($fp);
		fclose($fp);
	}
}

