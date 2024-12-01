<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\UserInterface;

/**
 * Class Request
 * @package Fluxoft\Rebar\Http
 * @property AuthInterface|null Auth              The auth module for this request, if any.
 * @property UserInterface|null AuthenticatedUser The authenticated user for this request, if any.
 * @property string Method   The HTTP method of the request (GET, POST, PUT, DELETE, etc.)
 * @property string Protocol Protocol used for the request (http, https, etc.)
 * @property string Host     Hostname of the request
 * @property int    Port     Port of the request
 * @property string URL      Full URL of the request
 * @property string URI      URI of the request
 * @property string Path     Path of the request
 * @property string RemoteIP IP address of the remote client
 * @property string RawBody  Raw body of the request. Immutable once set.
 * @property string Body     Body of the request. Mutable.
 * @property-read Session|null Session Session object for the request
 * @property-read Cookies|null Cookies Cookies object for the request
 * 
 * @method string Server(string $var = null, string $default = null) Retrieves one or all server parameters.
 * @method string Headers(string $var = null, string $default = null) Retrieves one or all headers.
 * @method string Get(string $var = null, string $default = null) Retrieves one or all GET parameters.
 * @method string Post(string $var = null, string $default = null) Retrieves one or all POST parameters.
 * @method string Put(string $var = null, string $default = null) Retrieves one or all PUT parameters.
 * @method string Patch(string $var = null, string $default = null) Retrieves one or all PATCH parameters.
 * @method string Delete(string $var = null, string $default = null) Retrieves one or all DELETE parameters.
 */
class Request {
	use GettableProperties;
	use SettableProperties;

	private Environment $environment;
	private ParameterSet $serverParamSet;
	private ParameterSet $headersParamSet;
	private ParameterSet $getParamSet;
	private ParameterSet $postParamSet;
	private ParameterSet $putParamSet;
	private ParameterSet $patchParamSet;
	private ParameterSet $deleteParamSet;
	
	/**
	 * Request constructor.
	 * Sets up the request object with the environment.
	 * Also sets up the properties array with the default properties. All properties are null by default,
	 * except for RawBody, which is set to the raw input from the environment (which can only be read once).
	 * 
	 * @param Environment $environment
	 */
	public function __construct(Environment $environment) {
		$this->environment = $environment;

		$this->properties = [
			'Auth' => null,
			'AuthenticatedUser' => null,
			'Method' => null,
			'Protocol' => null,
			'Host' => null,
			'Port' => null,
			'URL' => null,
			'URI' => null,
			'Path' => null,
			'RawBody' => null,
			'Body' => null,
			'RemoteIP' => null,
			'Session' => null,
			'Cookies' => null
		];
	}

	/**
	 * Retrieves one or all server parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Server($var = null, $default = null): mixed {
		if (!isset($this->serverParamSet)) {
			$this->serverParamSet = new ParameterSet($this->environment->ServerParams);
		}
		return $this->serverParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all headers.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Headers($var = null, $default = null): mixed {
		if (!isset($this->headersParamSet)) {
			$this->headersParamSet = new ParameterSet($this->environment->Headers);
		}
		return $this->headersParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all GET parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Get($var = null, $default = null): mixed {
		if (!isset($this->getParamSet)) {
			$this->getParamSet = new ParameterSet($this->environment->GetParams);
		}
		return $this->getParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all POST parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Post($var = null, $default = null): mixed {
		if (!isset($this->postParamSet)) {
			$params             = $this->environment->PostParams;
			$this->postParamSet = new ParameterSet($this->Method === 'POST' ? $params : []);
		}
		return $this->postParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all PUT parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Put($var = null, $default = null): mixed {
		if (!isset($this->putParamSet)) {
			$params            = $this->environment->PutParams;
			$this->putParamSet = new ParameterSet($this->Method === 'PUT' ? $params : []);
		}
		return $this->putParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all PATCH parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Patch($var = null, $default = null): mixed {
		if (!isset($this->patchParamSet)) {
			$params              = $this->environment->PatchParams;
			$this->patchParamSet = new ParameterSet($this->Method === 'PATCH' ? $params : []);
		}
		return $this->patchParamSet->Get($var, $default);
	}
	/**
	 * Retrieves one or all DELETE parameters.
	 *
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 *
	 * @inheritdoc \Fluxoft\Rebar\ParameterSet::Get
	 *
	 * @param string|null $var The parameter name.
	 * @param mixed|null  $default The default value to return if the parameter is not found.
	 * @return mixed|null The parameter value or the default value.
	 */
	public function Delete($var = null, $default = null): mixed {
		if (!isset($this->deleteParamSet)) {
			$params               = $this->environment->DeleteParams;
			$this->deleteParamSet = new ParameterSet($this->Method === 'DELETE' ? $params : []);
		}
		return $this->deleteParamSet->Get($var, $default);
	}

	protected function getMethod(): string {
		if (!isset($this->properties['Method'])) {
			if ($this->Headers('X-Http-Method-Override') !== null) {
				$method = $this->Headers('X-Http-Method-Override');
			} else {
				$method = $this->Server('REQUEST_METHOD', 'GET');
			}
			$this->properties['Method'] = strtoupper($method);
		}
		return $this->properties['Method'];
	}
	protected function setMethod(): void {
		throw new \InvalidArgumentException('Method is a read-only property.');
	}
	protected function getProtocol(): string {
		if (!isset($this->properties['Protocol'])) {
			if ($this->Headers('X-Forwarded-Proto') !== null) {
				$protocol = $this->Headers('X-Forwarded-Proto');
			} else {
				$protocol = $this->Server('REQUEST_SCHEME', 'http');
			}
			$this->properties['Protocol'] = strtolower($protocol);
		}
		return $this->properties['Protocol'];
	}
	protected function setProtocol(): void {
		throw new \InvalidArgumentException('Protocol is a read-only property.');
	}
	protected function getHost(): string {
		if (!isset($this->properties['Host'])) {
			$this->properties['Host'] = strtolower($this->Server('SERVER_NAME', 'localhost'));
		}
		return $this->properties['Host'];
	}
	protected function setHost(): void {
		throw new \InvalidArgumentException('Host is a read-only property.');
	}
	protected function getPort(): int {
		if (!isset($this->properties['Port'])) {
			if ($this->Headers('X-Forwarded-Port') !== null) {
				$this->properties['Port'] = (int) $this->Headers('X-Forwarded-Port');
			} else {
				$this->properties['Port'] = (int) $this->Server('SERVER_PORT', 80);
			}
		}
		return $this->properties['Port'];
	}
	protected function setPort(): void {
		throw new \InvalidArgumentException('Port is a read-only property.');
	}
	protected function getURL(): string {
		if (!isset($this->properties['URL'])) {
			$defaultPorts = [
				'http' => 80,
				'https' => 443
			];

			$url = $this->Protocol.'://'.$this->Host;
			if (isset($defaultPorts[$this->Protocol])
				&& $defaultPorts[$this->Protocol] !== $this->Port
			) {
				$url .= ':'.$this->Port;
			}
			$url .= rtrim($this->URI, '/');

			$this->properties['URL'] = $url;
		}
		return $this->properties['URL'];
	}
	protected function setURL(): void {
		throw new \InvalidArgumentException('URL is a read-only property.');
	}
	protected function getURI() {
		if (!isset($this->properties['URI'])) {
			$requestUri = $this->Server('REQUEST_URI', '');
			// convert any double slashes to single
			$requestUri              = preg_replace('|/+|', '/', $requestUri);
			$this->properties['URI'] = $requestUri;
		}
		return $this->properties['URI'];
	}
	protected function setURI(): void {
		throw new \InvalidArgumentException('URI is a read-only property.');
	}
	protected function getPath() {
		if (!isset($this->properties['Path'])) {
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

			$this->properties['Path'] = $path;
		}
		return $this->properties['Path'];
	}
	protected function setPath(): void {
		throw new \InvalidArgumentException('Path is a read-only property.');
	}
	protected function getRemoteIP() {
		if (!isset($this->properties['RemoteIP'])) {
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
			$this->properties['RemoteIP'] = $foundIP;
		}
		return $this->properties['RemoteIP'];
	}
	protected function setRemoteIP(): void {
		throw new \InvalidArgumentException('RemoteIP is a read-only property.');
	}
	protected function setRawBody(): void {
		throw new \InvalidArgumentException('RawBody is a read-only property.');
	}
	protected function getBody(): ?string {
		if (!isset($this->properties['RawBody'])) {
			$this->properties['RawBody'] = $this->environment->Input;
		}
		if (!isset($this->properties['Body'])) {
			$this->properties['Body'] = $this->properties['RawBody'] ?? null;
		}
		return $this->properties['Body'];
	}
	protected function setBody(string $body): void {
		if (!isset($this->properties['RawBody'])) {
			$this->properties['RawBody'] = $this->environment->Input;
		}
		$this->properties['Body'] = $body;
	}
	protected function setAuthenticatedUser(UserInterface $user) {
		$this->properties['AuthenticatedUser'] = $user;
	}
	protected function setAuth(AuthInterface $auth) {
		$this->properties['Auth'] = $auth;
	}
	protected function getSession(): Session {
		if (!isset($this->properties['Session'])) {
			$this->properties['Session'] = new Session();
		}
		return $this->properties['Session'];
	}
	protected function setSession(): void {
		throw new \InvalidArgumentException('Session is a read-only property.');
	}
	protected function getCookies(): Cookies {
		if (!isset($this->properties['Cookies'])) {
			$this->properties['Cookies'] = new Cookies($this->environment->CookieSettings);
		}
		return $this->properties['Cookies'];
	}
	protected function setCookies(): void {
		throw new \InvalidArgumentException('Cookies is a read-only property.');
	}
}
