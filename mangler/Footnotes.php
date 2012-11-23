<?php
namespace Mangler;

use \Acorn\TagParser\TagParser;

class Footnotes
{
	protected static $notes;
	protected static $named_entries;
	protected static $citations;
	protected static $slug;

	public static function reset($slug)
	{
		self::$slug  = $slug;
		self::$notes = array();
		self::$named_entries = array();
		if (true === SEPARATE_CITE_NOTE)
			self::$citations = array();
		else
			self::$citations = &self::$notes;
	}

	public static function mklatex($params, $content)
	{
		// TODO: combine $params to become the img paramters
		return sprintf('<img src="http://chart.apis.google.com/chart?cht=tx&chl=%s" />', urlencode($content));
	}

	public static function footnote($atts, $content)
	{
		// Calculate bullet number + name
		$num = count(self::$notes) + 1;

		// Make sure inner tags have higher numbers
		self::$notes[$num] = null;

		$note = new Note(self::$slug, $num, TagParser::parse($content));
		self::$notes[$num] = &$note;

		if (true === is_array($atts) && true === array_key_exists('name', $atts))
			self::$named_entries[$atts['name']] = &$note;

		return $note->getLink();
	}

	public static function cite(array $atts, $content)
	{
		// Calculate bullet number + name
		$num = count(self::$citations) + 1;

		if (array_key_exists('href', $atts))
			$href = $atts['href'];
		else
			$href = null;

		$note = new Citation(self::$slug, $num, $content, $href);
		self::$citations[$num] = &$note;

		if (array_key_exists('name', $atts))
			self::$named_entries[$atts['name']] = &$note;

		return $note->getLink();
	}

	function backref(array $atts, $content)
	{
		if (array_key_exists('name', $atts) && array_key_exists($atts['name'], self::$named_entries))
			return self::$named_entries[$atts['name']]->getLink();
		else
			return '';

		(string)$content;
	}

	public static function notes()
	{
		if (0 === count(self::$notes))
			return '';

		$ret = '<ol class="footnotes">' . PHP_EOL;
		foreach (self::$notes as $note)
		{
			$ret .= "\t" . $note->getEntry() . PHP_EOL;
		}
		$ret .= '</ol>' . PHP_EOL;

		return $ret;
	}

}

class Note
{
	protected $postId;
	protected $num;
	protected $content;
	protected $returns = array();

	public function __construct($postId, $num, $content)
	{
		$this->postId = $postId;
		$this->num = $num;
		$this->content = $content;
	}

	public function getEntryId()
	{
		return $this->postId . '-n-' . $this->num;
	}

	public function getLink()
	{
		$return = 'to-' . $this->getEntryId() . '-' . count($this->returns);
		$this->returns[] = $return;

		return sprintf(
			'<a href="#%s" class="footnote" id="%s" title="%s">%s</a>',
			$this->getEntryId(), $return, strip_tags($this->content), $this->num
		);
	}

	public function getEntry()
	{
		$entry = '<li id="' . $this->getEntryId() . '">' . PHP_EOL;
		$entry .= '<span class="note-marker">' . $this->num . '</span>' . PHP_EOL;

		foreach ($this->returns as $return)
		{
			$entry .= '<a class="note-return" href="#' . $return . '">&#x2191;</a>' . PHP_EOL;
		}

		$entry .= $this->content . PHP_EOL;
		$entry .= '</li>';

		return $entry;
	}

}

class Citation extends Note
{
	protected $href;

	protected static $blankText = 'Citation Needed';

	public function __construct($postId, $num, $content, $href)
	{
		parent::__construct($postId, $num, $content);
		$this->href = $href;
	}

	public function getEntryId()
	{
		return $this->postId . '-c-' . $this->num;
	}

	public function getLink()
	{
		$return = 'to-' . $this->getEntryId() . '-' . count($this->returns);
		$this->returns[] = $return;

		if ($this->href == null)
		{
			return sprintf(
				'<a href="#%s" class="citation" id="%s" title="%s">%s</a>',
				$this->getEntryId(), $return, strip_tags($this->content), self::$blankText
			);
		}
		else
		{
			return sprintf(
				'<a href="%s" class="citation" id="%s" target="_blank" title="%s">%s</a>',
				$this->href, $return, strip_tags($this->content), $this->num
			);
		}
	}

	public function getEntry()
	{
		$entry = '<li id="' . $this->getEntryId() . '">' . PHP_EOL;
		$entry .= '<span class="note-marker">' . $this->num . '</span>' . PHP_EOL;

		foreach ($this->returns as $return)
		{
			$entry .= '<a class="note-return" href="#' . $return . '">&#x2191;</a>' . PHP_EOL;
		}

		$entry .= $this->content . PHP_EOL;

		if ($this->href == null)
			$entry .= self::$blankText . PHP_EOL;
		else
			$entry .= '<a href="' . $this->href . '" target="_blank">' . $this->href . '</a>';

		$entry .= '</li>';

		return $entry;
	}
}

TagParser::addTag('ref',     '\Mangler\Footnotes::footnote');
TagParser::addTag('note',    '\Mangler\Footnotes::footnote');
TagParser::addTag('bakcref', '\Mangler\Footnotes::backref');
TagParser::addTag('cite',    '\Mangler\Footnotes::cite');
TagParser::addTag('lons',    '\Mangler\Footnotes::notes');
TagParser::addTag('latex',   '\Mangler\Footnotes::mklatex');

define('SEPARATE_CITE_NOTE', false);
