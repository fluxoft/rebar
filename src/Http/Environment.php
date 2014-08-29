<?php
namespace Fluxoft\Rebar\Http;

class Environment implements \ArrayAccess {
	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @var \Fluxoft\Rebar\Http\Environment
	 */
	protected static $environment = null;

	public static function GetInstance() {
		if (is_null(self::$environment)) {
			self::$environment = new self();
		}
		return self::$environment;
	}

	public static function GetMock(array $userSettings = array()) {
		$defaults = array(
			'REQUEST_METHOD' => 'GET',
			'SCRIPT_NAME' => '',
			'PATH_INFO' => '',
			'QUERY_STRING' => '',
			'SERVER_NAME' => 'localhost',
			'SERVER_PORT' => 80,
			'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
			'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'USER_AGENT' => 'Rebar',
			'REMOTE_ADDR' => '127.0.0.1',
			'rebar.protocol' => 'http',
			'rebar.input' => ''
		);
		self::$environment = new self(array_merge($defaults, $userSettings));

		return self::$environment;
	}

	private function __construct(array $settings = null) {
		if ($settings) {
			$this->properties = $settings;
		} else {
			$env = array();

			// The HTTP request method
			$env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

			// The IP address making the request.
			$env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

			/*
			 * Application paths
			 *
			 * This pulls two paths:
			 * SCRIPT_NAME is the real physical path to the application, be it in the root directory or a subdirectory
			 * of the public document root.
			 * PATH_INFO is the virtual path to the requested resource within the application context.
			 *
			 * With .htaccess, SCRIPT_NAME will be the absolute path minus file name. Without .htaccess it will also
			 * include the file name. If it is "/" it is set to an empty string (cannot have a trailing slash).
			 *
			 * PATH_INFO will be an absolute path with a leading slash, used for application routing.
			 */
			if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
				$env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
			} else {
				$env['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); //With URL rewrite
			}
			$env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
			if (strpos($env['PATH_INFO'], '?') !== false) {
				$env['PATH_INFO'] = substr_replace($env['PATH_INFO'], '', strpos($env['PATH_INFO'], '?')); //query string is not removed automatically
			}
			$env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
			$env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/');

			//The portion of the request URI following the '?'
			$env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

			//Name of server host that is running the script
			$env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

			//Number of server port that is running the script
			$env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

			//HTTP request headers
			$specialHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE');
			foreach ( $_SERVER as $key => $value ) {
				$value = is_string($value) ? trim($value) : $value;
				if ( strpos($key, 'HTTP_') === 0 ) {
					$env[substr($key, 5)] = $value;
				} else if ( strpos($key, 'X_') === 0 || in_array($key, $specialHeaders) ) {
					$env[$key] = $value;
				}
			}

			//Is the application running under HTTPS or HTTP protocol?
			$env['rebar.protocol'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

			//Input stream (readable one time only; not available for multi-part/form-data requests)
			$rawInput = @file_get_contents('php://input');
			if ( !$rawInput ) {
				$rawInput = '';
			}
			$env['rebar.input'] = $rawInput;

			$this->properties = $env;
		}
	}

	// ArrayAccess
	public function offsetExists($offset) {
		return isset($this->properties[$offset]);
	}
	public function offsetGet($offset) {
		if (!isset($this->properties[$offset])) {
			throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $offset));
		}
		return $this->properties[$offset];
	}
	public function offsetSet($offset, $value) {
		throw new \InvalidArgumentException('Read-only object.');
	}
	public function offsetUnset($offset) {
		throw new \InvalidArgumentException('Read-only object.');
	}
} 