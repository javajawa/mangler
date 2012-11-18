<?php
namespace Mangler\View;

use \Mangler\View;

class Page extends View
{
	protected $fh   = null;
	protected $text = null;

	public function __construct($file, $title = null)
	{
		if (file_exists($file))
		{
			$fh = fopen($file, 'rb');
			parent::__construct(fgets($fh));
			$this->fh = $fh;
		}
		else
		{
			parent::__construct($title);
			$this->text = $file;
		}
	}

	public function render()
	{
		$this->head();

		if ($this->fh)
		{
			fpassthru($this->fh);
			fclose($this->fh);
		}
		else
			echo $this->text;

		$this->foot();
	}
}
