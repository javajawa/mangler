<?php
namespace Mangler\Renderer;

use \Acorn\Renderer;

class Table extends Renderer
{
	protected $entities = array();
	protected $fields;
	protected $heading;

	public function __construct($view, $heading = null)
	{
		parent::__construct($view);
		$this->heading = $heading;
	}

	public function add(Row $e)
	{
		$this->entities []= $e;
	}

	protected function doRender()
	{
		if ($this->heading !== null)
			$v = '<h2>' . $this->heading . '</h2>' . PHP_EOL;
		else
			$v = '';

		$v .= '<table>';
		foreach ($this->entities as $entity)
		{
			$v .= "\t" . $entity->render() . PHP_EOL;
		}
		return $v . '</table>';
	}
}

abstract class Row extends Renderer
{
	protected $items = array();

	public function __construct($view)
	{
		parent::__construct($view);
	}

	protected function doRender()
	{
		$v = '<tr>';
		foreach ($this->items as $field)
			$v .= '<td>' . $field . '</td>';
		return $v . '</tr>';
	}
}
