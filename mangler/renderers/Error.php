<?php
namespace Mangler\Renderer;

use \Acorn\Renderer;

class Error extends Renderer
{
	private $code;
	private $message;
	private $frames = array();

	public function __construct($err)
	{
		if ($err instanceof \Exception)
		{
			$this->code = sprintf('%s [Code: %s]', get_class($err), $err->getCode());
			$this->message = (string)$err;
			$this->frames[] = array('file' => $err->getFile(), 'line' => $err->getLine());
			foreach ($err->getTrace() as $frame)
				$this->frames[] = $frame;
		}
		else
		{
			$this->code = \Acorn\Acorn::getErrorTypeName($err['type']);
			$this->message = $err['message'];

			$backtrace = debug_backtrace();
			while(true)
			{
				if (0 === count($backtrace))
				{
					$this->message .= '<h4>Unable to rebase stack trace: Performing full dump</h4>';
					$this->frames[] = $err;
					$backtrace = debug_backtrace();	break;
				}

				$frame = $backtrace[0];
				if (array_key_exists('line', $frame) && $frame['line'] === $err['line'])
					if (array_key_exists('file', $frame) && $frame['file'] === $err['file'])
						break;

				array_shift($backtrace);
			}

			foreach ($backtrace as $frame)
				$this->frames[] = $frame;
		}
	}

	public function doRender()
	{
		printf('<div class="error"><h2>%s</h2><p>%s</p></div>%s', $this->code, $this->message, PHP_EOL);

		foreach ($this->frames as $frame)
			$this->stackframe($frame);
	}

	public function stackframe($frame, $context = 4)
	{
		$file = array_key_exists('file', $frame) ? $frame['file'] : '';
		$line = array_key_exists('line', $frame) ? $frame['line'] : -1;
		if (false === file_exists($file))
		{
			echo '<code class="stackframe"><h4>Non-File call</h4></code>';
			return;
		}
		$fileContent = file($file);
		$fileContent = array_splice($fileContent, $line-$context-1, $context+$context+1);

		printf('<code class="stackframe"><h4>%s Line %d</h4>', $file, $line);

		foreach ($fileContent as $currline => $theline)
			echo $this->highlight($theline, $line-$context+$currline, $currline === $context).PHP_EOL;

		print '</code>'.PHP_EOL;
	}

	private function highlight($line, $number, $highlight)
	{
		$line = highlight_string('<?php ' . $line, true);
		$count = 1;
		$line = str_replace('&lt;?php&nbsp;', '', $line, $count);

		$line = str_replace(array('<code>', '</code>'), '', $line);
		$line = str_pad($number, 4, '0', STR_PAD_LEFT) . ' | ' . $line;

		if ($highlight)
			return '<div style="background: rgba(255, 255, 128, 0.3);">' . $line . '</div>';
		else
			return $line;
	}
}
