<?php
namespace Mangler;

use \Acorn\Acorn, \Acorn\Routes;

error_reporting(E_ALL | E_STRICT);

// Prevents date/time PHP warnings
date_default_timezone_set('UTC');

// Define paths to our folders
// TOP_PATH and PROJECT_PATH are defined in Acorn's bootstrap
define('CONTROLLER_PATH', PROJECT_PATH . 'controllers' . DIRECTORY_SEPARATOR);
define('RENDERER_PATH',   PROJECT_PATH . 'renderers'   . DIRECTORY_SEPARATOR);
define('ENTITY_PATH',     PROJECT_PATH . 'entities'    . DIRECTORY_SEPARATOR);
define('VIEW_PATH',       PROJECT_PATH . 'views'       . DIRECTORY_SEPARATOR);
define('PLUGIN_PATH',     TOP_PATH     . 'plugins'     . DIRECTORY_SEPARATOR);

define('RESOURCE_PATH', TOP_PATH . 'resources' . DIRECTORY_SEPARATOR);

// Classes in these namespaces can be found directly in these folders
Acorn::addClassPath(CONTROLLER_PATH . '%c.php', '\\Mangler\\Controller\\');
Acorn::addClassPath(ENTITY_PATH     . '%c.php', '\\Mangler\\Entity\\');
Acorn::addClassPath(RENDERER_PATH   . '%c.php', '\\Mangler\\Renderer\\');
Acorn::addClassPath(VIEW_PATH       . '%c.php', '\\Mangler\\View\\');
Acorn::addClassPath(PROJECT_PATH    . '%c.php', '\\Mangler\\');
Acorn::addClassPath(PLUGIN_PATH     . '%c.php', '\\Acorn\\');

// Controller namespace and basic routes
Routes::setNamespace('\\Mangler\\Controller');
Routes::set404('/error/404');

Routes::route('/error/?code', 'special', 'error');
Routes::route('/sticky/?name', 'special', 'page');

// Easter Eggs
Routes::route('/coffee', 'special', 'error', array('code' => 418));

// Time based archives
Routes::route('/', 'archive', 'time', array('page' => 0));
Routes::route('/page/?page', 'archive', 'time');

// Tag based archives
Routes::route('/tag/?tag', 'archive', 'tag', array('page' => 0));
Routes::route('/tag/?tag/?page', 'archive', 'tag');

// Tag based archives
Routes::route('/search', 'search', 'init');
Routes::route('/search/?query',       'search', 'search', array('page' => 0));
Routes::route('/search/?query/?page', 'search', 'search');

// An individual post
Routes::route('/post/?slug', 'post', 'get');

// Styles and other resources
Routes::route('/resources/style', 'resources', 'style');
Routes::route('/resources/js',    'resources', 'script');
Routes::route('/resources/img/?file', 'resources', 'image');

// RSS Feed
Routes::route('/feed', 'special', 'rss');

// Administration Panel
Routes::route('/admin', 'admin', 'index');
Routes::route('/admin/edit/?post', 'admin', 'edit');
Routes::route('/admin/publish/?post', 'admin', 'publish');
Routes::route('/admin/preview/?post', 'admin', 'preview');
Routes::route('/admin/create', 'admin', 'create');
Routes::route('/admin/delete/?post', 'admin', 'delete');
Routes::route('/admin/tag/?post', 'admin', 'tag');

/**
 * <p>Class containing some pieces of constant information about the site
 * along with some site-specific utility functions</p>
 */
class Site
{
	const title = 'Myself, Coding, Ranting, and Madness';
	const tagline = 'The Consciousness Stream Continues…';
	const sessionCookie = 'blog';

	public static function getUri($e)
	{
		if (is_string($e))
		{
			$stub = $e;
		}
		elseif ($e instanceof \Mangler\Entity\Post)
		{
			$stub = '/post/' . $e->slug;
		}
		elseif ($e instanceof \Mangler\Entity\Tag)
		{
			$stub = '/tag/' . $e->tag_slug;
		}
		else
		{
			trigger_error('Unable to find link for ' . $e, E_USER_ERROR);
		}

		return '//' . WWW_PATH . $stub;
	}
}

/**
 * <p>Class of constants representing different lengths of time</p>
 * <p>This is mainly intended for use with cache controls</p>
 */
class Time
{
	/**
	 * <p>Number of seconds in an hour, as measured on a clock</p>
	 */
	const HOUR        = 3600;
	/**
	 * <p>Number of seconds in the day, as measured on a clock</p>
	 */
	const DAY         = 86400;
	/**
	 * <p>Number of seconds in the average year</p>
	 * <p>Due to leap years etc., the mean year has 365.242222 days.</p>
	 */
	const SOLAR_YEAR  = 31556928;
	/**
	 * <p>Number of seconds in a Lunar month</p>
	 * <p>The length of Calendar months is much more variable than the
	 * 29.5306 days average of the Lunar month</p>
	 */
	const LUNAR_MONTH = 2551443;
}

