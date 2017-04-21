<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\UnsettableProperties;
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
 * @property string Body
 * @property string RemoteIP
 */
class Request extends Model {
	use UnsettableProperties;

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

		parent::__construct();
	}

	/** @var ParameterSet  */
	private $serverParamSet = null;
	public function Server($var = null, $default = null) {
		if (!isset($this->serverParamSet)) {
			$this->serverParamSet = new ParameterSet($this->environment->ServerParams);
		}
		return $this->serverParamSet->Get($var, $default);
	}
	/** @var ParameterSet */
	private $headersParamSet = null;
	public function Headers($var = null, $default = null) {
		if (!isset($this->headersParamSet)) {
			$this->headersParamSet = new ParameterSet($this->environment->Headers);
		}
		return $this->headersParamSet->Get($var, $default);
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
			$params             = $this->environment->PostParams;
			$this->postParamSet = new ParameterSet($this->Method === 'POST' ? $params : []);
		}
		return $this->postParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $putParamSet = null;
	public function Put($var = null, $default = null) {
		if (!isset($this->putParamSet)) {
			$params            = $this->environment->PutParams;
			$this->putParamSet = new ParameterSet($this->Method === 'PUT' ? $params : []);
		}
		return $this->putParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $patchParamSet = null;
	public function Patch($var = null, $default = null) {
		if (!isset($this->patchParamSet)) {
			$params              = $this->environment->PatchParams;
			$this->patchParamSet = new ParameterSet($this->Method === 'PATCH' ? $params : []);
		}
		return $this->patchParamSet->Get($var, $default);
	}
	/** @var ParameterSet  */
	private $deleteParamSet = null;
	public function Delete($var = null, $default = null) {
		if (!isset($this->deleteParamSet)) {
			$params               = $this->environment->DeleteParams;
			$this->deleteParamSet = new ParameterSet($this->Method === 'DELETE' ? $params : []);
		}
		return $this->deleteParamSet->Get($var, $default);
	}

	/** @var string */
	private $method = null;
	protected function getMethod() {
		if (!isset($this->method)) {
			if ($this->Headers('X-Http-Method-Override') !== null) {
				$method = $this->Headers('X-Http-Method-Override');
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
			if ($this->Headers('X-Forwarded-Proto') !== null) {
				$protocol = $this->Headers('X-Forwarded-Proto');
			} else {
				$protocol = $this->Server('REQUEST_SCHEME', 'http');
			}
			$this->protocol = strtolower($protocol);
		}
		return $this->protocol;
	}
	/** @var string */
	private $host = null;
	protected function getHost() {
		if (!isset($this->host)) {
			$this->host = strtolower($this->Server('SERVER_NAME', 'localhost'));
		}
		return $this->host;
	}
	/** @var integer */
	private $port = null;
	protected function getPort() {
		if (!isset($this->port)) {
			if ($this->Headers('X-Forwarded-Port') !== null) {
				$this->port = (integer) $this->Headers('X-Forwarded-Port');
			} else {
				$this->port = (integer) $this->Server('SERVER_PORT', 80);
			}
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
			$url .= rtrim($this->URI, '/');

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
			if (strpos($path, '?') !== false) {
				$path = substr_replace($path, '', strpos($path, '?'));
			}
			// trim trailing slash
			$path = rtrim($path, '/');
			// trim leading slash if present and prepend a single slash
			$path = '/'.ltrim($path, '/');

			$this->path = $path;
		}
		return $this->path;
	}
	private $remoteIP = null;
	protected function getRemoteIP() {
		if (!isset($this->remoteIP)) {
			$foundIP = null;
			$ips     = $this->Headers(
				'X-Forwarded-For',
				$this->Server('REMOTE_ADDR', '')
			);
			$ips     = explode(',', $ips);

			// return the first non-private IP address (forwarded IP string will list forwarded IPs in order,
			// so the earliest non-private IP (in case the user is behind a proxy/firewall) is the user's IP
			foreach ($ips as $ip) {
				$testIP = trim($ip);
				if (filter_var($testIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
					$foundIP = $testIP;
					break;
				}
			}

			// if somehow we got to this point, there is somehow only a private IP address
			if (!isset($foundIP)) {
				$foundIP = $this->Server('REMOTE_ADDR', 'invalid');
			}
			$this->remoteIP = $foundIP;
		}
		return $this->remoteIP;
	}
	private $body = null;
	protected function getBody() {
		if (!isset($this->body)) {
			$this->body = $this->environment->Input;
		}
		return $this->body;
	}
}
