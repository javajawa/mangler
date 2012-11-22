<?php
namespace Mangler\Renderer;

use \Acorn\Renderer;

class Table extends Renderer
{
	protected $entities = array();
	protected $fields;

	public function __construct($view)
	{
		parent::__construct($view);
	}

	public function add(Row $e)
	{
		$this->entities []= $e;
	}

	protected function doRender()
	{
		$v = '<table>';
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
