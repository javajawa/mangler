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
		$this->timestamp = date('j M Y G:i', strtotime($this->timestamp));
	}
}

