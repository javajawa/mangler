<?php
namespace \Acorn\Table;

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
		}
		return $v . '</table>';
	}
}

class Row extends Renderer
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
		$v .= '<tr>';
		foreach ($entity as $field)
			$v .= '<td>' . $field . '</td>';
		return $v . '</tr>';
	}
}
