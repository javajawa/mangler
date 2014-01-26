<?php
namespace Acorn\TagParser;

error_reporting(E_ALL);
setlocale(LC_ALL, 'en_GB.UTF-8');

class TagParser
{
	protected static $tags = array();
	protected static $matchString;

	public static function addTag($name, $handler)
	{
		self::$tags[$name] = $handler;
	}

	public static function parse($str)
	{
		$matchString = implode('|', array_keys(self::$tags));
		$matchString = sprintf('/\<(%s)(.*)(\/)?\>/smU', $matchString);

		$offset  = 0;
		$end     = 0;
		$matches = array();
		$output  = '';

		while (1 === preg_match($matchString, $str, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			$offset  = $matches[0][1] + strlen($matches[0][0]);
			$tag     = $matches[1][0];
			$params  = $matches[2][0];

			// Capture non-tag content
			$output .= substr($str, $end, $matches[0][1] - $end);

			if (false === empty($matches[3][0])) // Self-closing tag
			{
				$end     = $offset;
				$content = '';
			}
			else
			{
				$end     = self::match($str, $offset, $tag);
				$content = substr($str, $offset, $end - $offset - strlen($tag) - 3);
			}
			$output .= self::process($tag, $params, $content);
			$offset  = $end;
		}

		$output .= substr($str, $end);
		return $output;
	}

	public static function strip($str)
	{
		$matchString = implode('|', array_keys(self::$tags));
		$matchString = sprintf('/\<(%s)(.*)(\/)?\>/smU', $matchString);

		$offset  = 0;
		$end     = 0;
		$matches = array();
		$output  = '';

		while (1 === preg_match($matchString, $str, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			// Capture non-tag content
			$output .= substr($str, $end, $matches[0][1] - $end);

			$offset  =
			$end     = $matches[0][1] + strlen($matches[0][0]);

			if (empty($matches[3][0])) // Not a Self-closing tag
			{
				$tag     = $matches[1][0];

				$offset  =
				$end     = self::match($str, $offset, $tag);
			}

			$offset  = $end;
		}

		$output .= substr($str, $end);
		return $output;
	}

	protected static function match($str, $offset, $tag)
	{
		$depth = 1;
		$matchString = sprintf('/\<(?:%s.*(\/)?|(\/)%s)\>/smU', $tag, $tag);
		$matches = array();

		while ($depth > 0)
		{
			$result = preg_match($matchString, $str, $matches, PREG_OFFSET_CAPTURE, $offset);

			if (1 !== $result)
			{
				return strlen($str);
			}

			if (array_key_exists(2, $matches)) // Match 2 is only set in a close tag
			{
				--$depth;
			}
			else if (!array_key_exists(1, $matches)) // Only match 1 set in an opening tag
			{
				++$depth;
			}

			$offset = $matches[0][1] + strlen($matches[0][0]);
		}
		return $offset;
	}

	protected static function process($tag, $params, $content)
	{
		preg_match_all('/([a-z]+)="(.*)"/simU', $params, $params, PREG_SET_ORDER);
		$attrs = array();
		foreach ($params as $param)
		{
			$attrs[$param[1]] = $param[2];
		}

		$callable = self::$tags[$tag];

		return call_user_func($callable, $attrs, $content);
	}
}
