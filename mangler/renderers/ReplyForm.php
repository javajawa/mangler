<?php
namespace Mangler\Renderer;

use \Acorn\Renderer,
	\Mangler\View;

class ReplyForm extends Renderer
{
	protected $post;

	public function __construct($to, View $view)
	{
		parent::__construct($view);
		$this->post = $to;
	}

	protected function doRender()
	{
		if (true === isset($_SESSION['user']))
		{
			$handle = $_SESSTION['user']->handle;
			$email = $_SESSTION['user']->email;
		}
		else
		{
			$handle = isset($_POST['handle']) ? $_POST['handle'] : '';
			$email  = isset($_POST['email'])  ? $_POST['email']  : '';
		}
		$content  = isset($_POST['content'])  ? $_POST['content']  : '';

		if (isset($_SESSION['reply-flash']))
		{
			$flash = '<div class="flash center">' . $_SESSION['reply-flash'] . '</div>';
			unset($_SESSION['reply-flash']);
		}
		else
		{
			$flash = '';
		}

		return <<<EOF
<form id="reply" action="{$this->view->getUri('/reply/' . $this->post)}" method="POST">
	<fieldset>
		<h3>Add Your Thoughts</h3>
		{$flash}
		<label for="handle">Name:</label>
		<input id="handle" name="handle" value="{$handle}" />

		<label for="email">Email:</label>
		<input id="email" name="email" value="{$email}" />

		<textarea name="content">{$content}</textarea>

		<input type="submit" value="Post" />
	</fieldset>
</form>

EOF;
	}
}

