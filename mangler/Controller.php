<?php
namespace Mangler;

/**
 * 	Controller
 * 	All controllers _must_ extend this badman.
 * 	Add methods you want all controllers to be able to access.
 */
class Controller extends \Acorn\Controller
{

	/**
	 * <p>Stores the mime type of the response</p>
	 * <p>This setting has no meaning in a non-HTTP context.</p>
	 * @var string the mime type of the response
	 */
	private $contentType;
	/**
	 * <p>Stores the HTTP response code to set</p>
	 * <p>This setting has no meaning in a non-HTTP context.</p>
	 * @var int the HTTP response code to set
	 */
	protected $responseCode;
	/**
	 * <p>Store the cache time to send. If set to 0, don't-cache headers are sent.
	 * If set to -1, cache headers are not explicitly set.</p>
	 * <p>This setting has no meaning in a non-HTTP context.</p>
	 * @param int cache time in seconds
	 */
	protected $cacheTime = -1;
	/**
	 * <p>If the cahce time is greater than zero, this flag controls whether the
	 * 'Cache-control: public' header is sent</p>
	 * <p>This setting has no meaning in a non-HTTP context.</p>
	 * @param boolean $cachePublic
	 */
	protected $cachePublic = false;
	/**
	 * <p>ETag to send</p>
	 * <p>If flase, no etag is sent. If true, an etag is generated. If a string,
	 * that value is sent as an etag.</p>
	 * <p>Note: if the etag matches the request etag, then the system will
	 * automatically send a 304 Not Modified and no content</p>
	 * <p>This setting has no meaning in a non-HTTP context.</p>
	 * @param string|boolean $etag etag data
	 */
	protected $eTag = false;

	public function __construct($contentType = 'text/plain', $session = false)
	{
		parent::__construct();
		$this->contentType = $contentType;

		if (true === $session)
		{
			session_name(Site::sessionCookie);
			session_set_cookie_params(time() + 3600, getenv('PUBLIC_PATH'));
			session_start();
		}
	}

	public function before()
	{
		switch ($this->contentType)
		{
			case 'application/json':
				ob_start('json_encode');
				break;
			case 'text/html':
			case 'text/css':
			case 'text/javascript':
				ob_start(array(&$this, 'min'));
				break;
			default:
				ob_start();
		}
	}

	public function after()
	{
		header('Content-type: ' . $this->contentType .'; charset=UTF-8', true, $this->responseCode);

		// HTTP Cache-control
		if (0 < $this->cacheTime)
		{
			header('Vary: Accept-Encoding');
			header('Cache-Control: max-age=' . (int)$this->cacheTime);
			header('Expires: ' . date('r', time() + (int)$this->cacheTime));
			header('Cache-Control: ' . ($this->cachePublic ? 'public' : 'private'), false);
		}
		else if (0 === $this->cacheTime)
		{
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Thu, 1 Jan 1970 00:00:00 +0000');
		}

		// ETagging
		if (false !== $this->eTag)
		{
			$requestETag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;

			if (true === $this->eTag)
			{
				$this->eTag = md5(ob_get_contents());
			}

			header('ETag: ' . $this->eTag);
			if ($this->eTag === $requestETag)
			{
				header('Content-type: ' . $this->contentType, true, 304);
				return;
			}
		}

		// Send content
		ob_end_flush();
	}

	public function resetBuffer($contentType)
	{
		$this->contentType = $contentType;
		ob_end_clean();
		$this->before();
	}

	public function redirect($uri, $code = 303)
	{
		ob_end_clean();

		if (1 === preg_match('#^https?://#', $uri))
		{
			header('Location: ' . $uri, true, $code);
		}
		else
		{
			header('Location: http://' . WWW_PATH . $uri, true, $code);
		}
	}

	public function ifMatch($tag = false)
	{
		if (false === $tag)
		{
			if (false === $this->eTag)
			{
				return false;
			}
			else
			{
				$tag = $this->eTag;
			}
		}

		if (false === isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			return false;
		}

		return ($tag === $_SERVER['HTTP_IF_NONE_MATCH']);
	}

	public function handleException(\Exception $ex)
	{
		while (0 !== ob_get_level())
		{
			ob_end_clean();
		}

		$exceptions = array();
		while (null !== $ex)
		{
			$exceptions[] = new \Acorn\Error($ex);
			$ex = $ex->getPrevious();
		}

		$view = new \Mangler\View\Error($exceptions);
		$view->render();

		exit;
	}

	public function handleError(array $err)
	{
		if (!(error_reporting() & $err['type']))
		{
			return true;
		}

		while (0 !== ob_get_level())
		{
			ob_end_clean();
		}

		$view = new \Mangler\View\Error(array(
			new \Acorn\Error($err)
		));
		$view->render();

		exit;
	}

	public function setContentType($type)
	{
		$this->contentType = $type;
	}

	public function min($str)
	{
		return preg_replace('/[\n\t ][\n\t ]+/', ' ', $str);
	}
}
