<?php
namespace Mangler;

use \Acorn\TagParser\TagParser;

class Footnotes
{
	protected static $notes = array();

	public static function reset()
	{
		self::$notes = array();
	}

	public static function footnote($params, $content)
	{
		$index = count(self::$notes);
		self::$notes[$index] = null;
		self::$notes[$index] = TagParser::parse($content);
		return '<sup>[' . ($index + 1) . ']</sup>';
	}

	public static function cite($params, $content)
	{
		$index = count(self::$notes);
		self::$notes[$index] = null;
		self::$notes[$index] = TagParser::parse($content);

		if (empty(self::$notes[$index])) self::$notes[$index] = '<a href="' . $params['href'] . '">' . $params['href'] . '</a>';

		return '<sup><a href="' . $params['href'] . '">[' . ($index + 1) . ']</a></sup>';
	}

	public static function notes(array $params, $content)
	{
		if (count(self::$notes) === 0)
			return '';

		$r = '<h3>Footnotes</h3>' . $content . '<ol>';
		foreach (self::$notes as $note)
			$r .= '<li>' . $note . '</li>';
		$r .= '</ol>';

		self::$notes = array();
		return $r;
		(array)$params;
	}

	function mklatex($params, $content)
	{
		// TODO: combine $params to become the img paramters
		return sprintf('<img src="http://chart.apis.google.com/chart?cht=tx&chl=%s" />', urlencode($content));
	}

}

TagParser::addTag('ref',   '\Mangler\Footnotes::footnote');
TagParser::addTag('note',  '\Mangler\Footnotes::footnote');
TagParser::addTag('cite',  '\Mangler\Footnotes::cite');
TagParser::addTag('lons',  '\Mangler\Footnotes::notes');
TagParser::addTag('latex', '\Mangler\Footnotes::mklatex');
