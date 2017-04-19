<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Model;

/**
 * Class Request
 * @package Fluxoft\Rebar\Http
 * @property string Method
 * @property string Protocol
 * @property string Host
 * @property int Port
 * @property string URL
 * @property string URI
 * @property string Path
 * @property array Headers
 * @property string Body
 * @property string RemoteIP
 */
class Request extends Model {
	protected $properties = [
		'Method' => 'GET',
		'Protocol' => 'http',
		'Host' => 'localhost',
		'Port' => 80,
		'URL' => 'http://localhost/',
		'URI' => '/',
		'Path' => '/',
		'Headers' => [],
		'Body' => '',
		'RemoteIP' => '0.0.0.0'
	];

	private $environment;

	/**
	 * @param Environment $environment
	 */
	public function __construct(Environment $environment) {
		$this->environment = $environment;

		/*$this->getParamSet    = new ParameterSet($environment['get']);
		$this->postParamSet   = new ParameterSet($environment['post']);
		$this->putParamSet    = new ParameterSet($environment['put']);
		$this->patchParamSet  = new ParameterSet($environment['patch']);
		$this->deleteParamSet = new ParameterSet($environment['delete']);*/

		parent::__construct();
		/*parent::__construct([
			'Method' => $environment['method'],
			'PathInfo' => $environment['pathInfo'],
			'Headers' => $environment['headers'],
			'Environment' => $environment,
			'Body' => $environment['rebar.input']
		]);*/
	}

	/** @var ParameterSet  */
	private $serverParamSet = null;
	public function Server($var = null, $default = null) {
		if (!isset($this->serverParamSet)) {
			$this->serverParamSet = new ParameterSet($this->environment->ServerParams);
		}
		return $this->serverParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $getParamSet = null;
	public function Get($var = null, $default = null) {
		if (!isset($this->getParamSet)) {
			$this->getParamSet = new ParameterSet($this->environment->GetParams);
		}
		return $this->getParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $postParamSet = null;
	public function Post($var = null, $default = null) {
		if (!isset($this->postParamSet)) {
			$this->postParamSet = new ParameterSet($this->environment->PostParams);
		}
		return $this->postParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $putParamSet = null;
	public function Put($var = null, $default = null) {
		if (!isset($this->putParamSet)) {
			$this->putParamSet = new ParameterSet($this->environment->PutParams);
		}
		return $this->putParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $patchParamSet = null;
	public function Patch($var = null, $default = null) {
		if (!isset($this->patchParamSet)) {
			$this->patchParamSet = new ParameterSet($this->environment->PatchParams);
		}
		return $this->patchParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $deleteParamSet = null;
	public function Delete($var = null, $default = null) {
		if (!isset($this->deleteParamSet)) {
			$this->deleteParamSet = new ParameterSet($this->environment->DeleteParams);
		}
		return $this->deleteParamSet->Get($var, $default);
	}

	/** @var string */
	private $method = null;
	protected function getMethod() {
		if (!isset($this->method)) {
			if (isset($this->Headers['X-HTTP-Method-Override'])) {
				$method = $this->Headers['X-HTTP-Method-Override'];
			} else {
				$method = $this->Server('REQUEST_METHOD', 'GET');
			}
			$this->method = strtoupper($method);
		}
		return $this->method;
	}
	/** @var string */
	private $protocol = null;
	protected function getProtocol() {
		if (!isset($this->protocol)) {
			$this->protocol = $this->Server('REQUEST_SCHEME', 'http');
		}
		return $this->protocol;
	}
	/** @var string */
	private $host = null;
	protected function getHost() {
		if (!isset($this->host)) {
			$this->host = $this->Server('SERVER_NAME', 'localhost');
		}
		return $this->host;
	}
	/** @var integer */
	private $port = null;
	protected function getPort() {
		if (!isset($this->port)) {
			$this->port = (integer) $this->Server('SERVER_PORT', 80);
		}
		return $this->port;
	}
	/** @var string */
	private $url = null;
	protected function getURL() {
		if (!isset($this->url)) {
			$defaultPorts = [
				'http' => 80,
				'https' => 443
			];

			$url = $this->Protocol.'://'.$this->Host;
			if (isset($defaultPorts[$this->Protocol]) &&
				$defaultPorts[$this->Protocol] !== $this->Port
			) {
				$url .= ':'.$this->Port;
			}
			$url .= $this->URI;

			$this->url = $url;
		}
		return $this->url;
	}
	/** @var string */
	private $uri = null;
	protected function getURI() {
		if (!isset($this->uri)) {
			$requestUri = $this->Server('REQUEST_URI', '');
			// convert any double slashes to single
			$requestUri = preg_replace('|/+|', '/', $requestUri);
			$this->uri  = $requestUri;
		}
		return $this->uri;
	}
	/** @var string */
	private $path = null;
	protected function getPath() {
		if (!isset($this->path)) {
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
			$requestUri = $this->URI;
			$scriptName = $this->Server('SCRIPT_NAME', '');

			if (strpos($requestUri, $scriptName) === false) {
				$scriptName = str_replace('\\', '/', dirname($scriptName));
			}

			// remove script name from request URI
			$path = substr_replace($requestUri, '', 0, strlen($scriptName));
			// strip off querystring
			$path = substr_replace($path, '', strpos($path, '?'));
			// trim trailing slash
			$path = rtrim($path, '/');
			// trim leading slash if present and prepend a single slash
			$path = '/'.ltrim($path, '/');

			$this->path = $path;
		}
		return $this->path;
	}
	protected function getRemoteIP() {
		if (array_key_exists('X-Forwarded-For', $this->Headers)) {
			$ips = $this->Headers['X-Forwarded-For'];
		} elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $this->Headers)) {
			$ips = $this->Headers['HTTP_X_FORWARDED_FOR'];
		} else {
			$ips = $this->Server('REMOTE_ADDR', '');
			//$ips = $this->Environment['REMOTE_ADDR'];
		}
		$ips = explode(',', $ips);

		// return the first non-private IP address (forwarded IP string will list forwarded IPs in order,
		// so the earliest non-private IP (in case the user is behind a proxy/firewall) is the user's IP
		foreach ($ips as $ip) {
			$ip = trim($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
				return $ip;
			}
		}

		// if somehow we got to this point, there is somehow only a private IP address
		return $this->Server('REMOTE_ADDR', 'invalid');
	}
	protected function getHeaders() {
		return $this->environment->Headers;
	}
	protected function getBody() {
		return $this->environment->Input;
	}

	public function __set($key, $value) {
		throw new \InvalidArgumentException('Read-only object.');
	}
}
