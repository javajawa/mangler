<?php

namespace Mangler\Entity;

use \Acorn\Entity;

class Comment extends Entity
{
	protected $id;
	protected $timestamp;
	protected $title;
	protected $content;
	protected $user;
	protected $email;

	public function __construct()
	{
		$this->timestamp = strtotime($this->timestamp);
	}

	public function __get($name)
	{
		if ('timestamp' === $name)
			return date('j M Y G:i', $this->timestamp);
		return parent::__get($name);
	}
}

