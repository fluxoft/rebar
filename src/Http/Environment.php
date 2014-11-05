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
		$defaults          = array(
			'method' => 'GET',
			'SCRIPT_NAME' => '',
			'pathInfo' => '',
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
			$env['method'] = strtoupper($_SERVER['REQUEST_METHOD']);

			// The IP address making the request.
			$env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

			/*
			 * Application paths
			 *
			 * This pulls two paths:
			 * SCRIPT_NAME is the real physical path to the application, be it in the root directory or a subdirectory
			 * of the public document root.
			 * pathInfo is the virtual path to the requested resource within the application context.
			 *
			 * With .htaccess, SCRIPT_NAME will be the absolute path minus file name. Without .htaccess it will also
			 * include the file name. If it is "/" it is set to an empty string (cannot have a trailing slash).
			 *
			 * pathInfo will be an absolute path with a leading slash, used for application routing.
			 */
			if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
				$env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
			} else {
				$env['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); //With URL rewrite
			}
			$env['pathInfo'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
			if (strpos($env['pathInfo'], '?') !== false) {
				$env['pathInfo'] = substr_replace(
					$env['pathInfo'], '', strpos($env['pathInfo'], '?')
				); //query string is not removed automatically
			}
			$env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
			$env['pathInfo']    = '/' . ltrim($env['pathInfo'], '/');

			//The portion of the request URI following the '?'
			$env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

			//Name of server host that is running the script
			$env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

			//Number of server port that is running the script
			$env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

			//HTTP request headers
			$specialHeaders = array(
				'CONTENT_TYPE',
				'CONTENT_LENGTH',
				'PHP_AUTH_USER',
				'PHP_AUTH_PW',
				'PHP_AUTH_DIGEST',
				'AUTH_TYPE'
			);
			foreach ($_SERVER as $key => $value) {
				$value = is_string($value) ? trim($value) : $value;
				if (strpos($key, 'HTTP_') === 0) {
					$env[substr($key, 5)] = $value;
				} elseif (strpos($key, 'X_') === 0 || in_array($key, $specialHeaders)) {
					$env[$key] = $value;
				}
			}

			//Is the application running under HTTPS or HTTP protocol?
			$env['rebar.protocol'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

			//Input stream (readable one time only; not available for multi-part/form-data requests)
			$rawInput = file_get_contents('php://input');
			if ($rawInput === false) {
				$rawInput = '';
			}
			$env['rebar.input'] = $rawInput;

			$env['headers'] = $this->getAllHeaders();

			// GET and POST arrays
			$env['get']    = $_GET;
			$env['post']   = array();
			$env['put']    = array();
			$env['patch']  = array();
			$env['delete'] = array();

			if (isset($env['headers']['X-HTTP-Method-Override'])) {
				$env['post'] = array();
				switch ($env['headers']['X-HTTP-Method-Override']) {
					case 'PUT':
						$env['method'] = 'PUT';
						$env['put']    = $_POST;
						break;
					case 'PATCH':
						$env['method'] = 'PATCH';
						$env['patch']  = $_POST;
						break;
					case 'DELETE':
						$env['method'] = 'DELETE';
						$env['delete'] = $_POST;
						break;
				}
			} else {
				$env['post'] = $_POST;
			}

			$this->properties = $env;
		}
	}

	private function getAllHeaders() {
		if (function_exists('getallheaders')) {
			return getallheaders();
		} else {
			$out = array();
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) == "HTTP_") {
					$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));

					$out[$key] = $value;
				} else {
					$out[$key] = $value;
				}
			}
			return $out;
		}
	}

	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}

	public function __get($var) {
		return $this->properties[$var];
	}
	public function __set($var, $value) {
		throw new \InvalidArgumentException(sprintf('Read-only object.'));
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
