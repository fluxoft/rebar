<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\_Traits\StringableProperties;
use Fluxoft\Rebar\_Traits\UnsettableProperties;
use Fluxoft\Rebar\Http\Exceptions\EnvironmentException;

/**
 * Class Environment
 * @package Fluxoft\Rebar\Http
 * @property-read array  $ServerParams This is the $_SERVER superglobal
 * @property-read array  $GetParams This is the $_GET superglobal
 * @property-read array  $PostParams This is the $_POST superglobal
 * @property-read array  $PutParams This is the $_POST superglobal, but only if the request method is PUT
 * @property-read array  $PatchParams This is the $_POST superglobal, but only if the request method is PATCH
 * @property-read array  $DeleteParams This is the $_POST superglobal, but only if the request method is DELETE
 * @property-read array  $Headers This is an array of all headers sent in the request
 * @property-read string $Input This is the raw input from the request
 * @property      array  $CookieSettings This is an array of settings for cookies, valid keys are:
 *  - expires: The expiration time of the cookie. Default is 0 (session cookie)
 *  - path: The path on the server in which the cookie will be available on. Default is '/'
 *  - domain: The (sub)domain that the cookie is available to. Default is the host of the server
 *  - secure: Indicates that the cookie should only be transmitted over a secure HTTPS connection
 *  - httponly: When TRUE the cookie will be made accessible only through the HTTP protocol
 * @method void SetCookieSettings(array $settings) Set the cookie settings. Valid keys are same as CookieSettings
 */
class Environment implements \ArrayAccess, \Iterator {
	use GettableProperties;
	use UnsettableProperties;
	use IterableProperties;
	use ArrayAccessibleProperties;
	use StringableProperties;

	/**
	 * @var \Fluxoft\Rebar\Http\Environment
	 */
	protected static $environment = null;

	public static function GetInstance(): Environment {
		if (is_null(static::$environment)) {
			static::$environment = new static();
		}
		return static::$environment;
	}
	public function __clone(): void {
		throw new EnvironmentException('Cloning not allowed.');
	}
	final private function __construct() {
		$this->configureDefaultCookieSettings();
	}

	protected array $defaultCookieSettings = [
		'expires'  => 0, // Default to session cookies
		'path'     => '/', // Default to root path
		'domain'   => null, // Will be dynamically set in the constructor
		'secure'   => null, // Will be dynamically set in the constructor
		'httponly' => true // Default to HTTP-only cookies for security
	];
	protected function configureDefaultCookieSettings(): void {
		$serverParams = array_change_key_case($this->ServerParams, CASE_LOWER);

		$httpHost = $serverParams['http_host'] ?? null;
		if ($httpHost) {
			$this->defaultCookieSettings['domain'] = explode(':', $httpHost)[0];
		} else {
			$this->defaultCookieSettings['domain'] = null;
		}
		$this->defaultCookieSettings['secure'] =
			(isset($serverParams['https']) && strtolower($serverParams['https']) !== 'off') ||
			(isset($serverParams['http_x_forwarded_proto']) && strtolower($serverParams['http_x_forwarded_proto']) === 'https');
		$this->properties['CookieSettings']    = $this->defaultCookieSettings;
	}

	protected function getServerParams(): array {
		if (!isset($this->properties['ServerParams'])) {
			$this->properties['ServerParams'] = $this->superGlobalServer();
		}
		return $this->properties['ServerParams'];
	}
	protected function getGetParams(): array {
		if (!isset($this->properties['GetParams'])) {
			$this->properties['GetParams'] = $this->superGlobalGet();
		}
		return $this->properties['GetParams'];
	}
	protected function getPostParams(): array {
		if (!isset($this->properties['PostParams'])) {
			if (isset($this->ServerParams['REQUEST_METHOD']) &&
				strtoupper($this->ServerParams['REQUEST_METHOD']) === 'POST' &&
				!isset($this->Headers['X-Http-Method-Override'])
			) {
				$this->properties['PostParams'] = $this->superGlobalPost();
			} elseif (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'POST'
			) {
				$this->properties['PostParams'] = $this->superGlobalPost();
			} else {
				$this->properties['PostParams'] = [];
			}
		}
		return $this->properties['PostParams'];
	}
	protected function getPutParams(): array {
		if (!isset($this->properties['PutParams'])) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'PUT'
			) {
				$this->properties['PutParams'] = $this->superGlobalPost();
			} else {
				$this->properties['PutParams'] = [];
			}
		}
		return $this->properties['PutParams'];
	}
	protected function getPatchParams(): array {
		if (!isset($this->properties['PatchParams'])) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'PATCH'
			) {
				$this->properties['PatchParams'] = $this->superGlobalPost();
			} else {
				$this->properties['PatchParams'] = [];
			}
		}
		return $this->properties['PatchParams'];
	}
	protected function getDeleteParams(): array {
		if (!isset($this->properties['DeleteParams'])) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'DELETE'
			) {
				$this->properties['DeleteParams'] = $this->superGlobalPost();
			} else {
				$this->properties['DeleteParams'] = [];
			}
		}
		return $this->properties['DeleteParams'];
	}
	protected function getHeaders(): array {
		if (!isset($this->properties['Headers'])) {
			$this->properties['Headers'] = $this->getAllHeaders();
		}
		return $this->properties['Headers'];
	}
	protected function getInput(): string {
		if (!isset($this->properties['Input'])) {
			$this->properties['Input'] = $this->getRawInput();
		}
		return $this->properties['Input'];
	}

	// CookieSettings
	public function SetCookieSettings(array $settings): void {
		$this->properties['CookieSettings'] = $this->validateCookieSettings($settings);
	}
	protected function validateCookieSettings(array $settings): array {
		$validated = [];
		foreach ($this->defaultCookieSettings as $key => $value) {
			if (isset($settings[$key])) {
				$validated[$key] = $settings[$key];
			} else {
				$validated[$key] = $value;
			}
		}

		// Detect unexpected keys and throw an exception
		$unexpectedKeys = array_diff(array_keys($settings), array_keys($this->defaultCookieSettings));
		if (count($unexpectedKeys) > 0) {
			throw new EnvironmentException('Unexpected cookie settings: ' . implode(', ', $unexpectedKeys));
		}

		return $validated;
	}


	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function getRawInput() {
		$rawInput = file_get_contents('php://input');
		return ($rawInput === false) ? '' : $rawInput;
	}

	protected function superGlobalServer() {
		return $_SERVER; // @codeCoverageIgnore
	}
	protected function superGlobalGet() {
		return $_GET; // @codeCoverageIgnore
	}
	protected function superGlobalPost() {
		return $_POST; // @codeCoverageIgnore
	}

	protected function getAllHeaders() {
		$headers = [];
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		}

		$copy_server = array(
			'CONTENT_TYPE'   => 'Content-Type',
			'CONTENT_LENGTH' => 'Content-Length',
			'CONTENT_MD5'    => 'Content-Md5',
		);
		foreach ($this->ServerParams as $key => $value) {
			if (substr($key, 0, 5) === 'HTTP_') {
				$key = substr($key, 5);
				if (!isset($copy_server[$key]) || !isset($this->ServerParams[$key])) {
					$key           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
					$headers[$key] = $value;
				}
			} elseif (isset($copy_server[$key])) {
				$headers[$copy_server[$key]] = $value;
			}
		}
		if (!isset($headers['Authorization'])) {
			if (isset($this->ServerParams['REDIRECT_HTTP_AUTHORIZATION'])) {
				$headers['Authorization'] = $this->ServerParams['REDIRECT_HTTP_AUTHORIZATION'];
			} elseif (isset($this->ServerParams['PHP_AUTH_USER']) &&
				isset($this->serverParams['PHP_AUTH_PW'])
			) {
				$basic_pass               = isset($this->ServerParams['PHP_AUTH_PW']) ? $this->ServerParams['PHP_AUTH_PW'] : '';
				$headers['Authorization'] = 'Basic ' . base64_encode($this->ServerParams['PHP_AUTH_USER'] . ':' . $basic_pass);
			} elseif (isset($this->ServerParams['PHP_AUTH_DIGEST'])) {
				$headers['Authorization'] = $this->ServerParams['PHP_AUTH_DIGEST'];
			}
		}
		return $headers;
	}
}
