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
 * @property array ServerParams
 * @property array GetParams
 * @property array PostParams
 * @property array PutParams
 * @property array PatchParams
 * @property array DeleteParams
 * @property array Headers
 * @property string Input
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

	public static function GetInstance() {
		if (is_null(static::$environment)) {
			static::$environment = new static();
		}
		return static::$environment;
	}
	public static function ResetInstance(): void {
		static::$environment = null;
	}	
	public function __clone() {
		throw new EnvironmentException('Cloning not allowed.');
	}
	private function __construct() {
		$this->properties['ServerParams'] = [];
		$this->properties['GetParams']    = [];
		$this->properties['PostParams']   = [];
		$this->properties['PutParams']    = [];
		$this->properties['PatchParams']  = [];
		$this->properties['DeleteParams'] = [];
		$this->properties['Headers']      = [];
		$this->properties['Input']        = '';
	}

	/** @var array */
	protected function getServerParams() {
		if (!isset($this->properties['ServerParams'])) {
			$this->properties['ServerParams'] = $this->superGlobalServer();
		}
		return $this->properties['ServerParams'];
	}
	/** @var array */
	protected function getGetParams() {
		if (!isset($this->properties['GetParams'])) {
			$this->properties['GetParams'] = $this->superGlobalGet();
		}
		return $this->properties['GetParams'];
	}
	/** @var array */
	protected function getPostParams() {
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
	/** @var array */
	protected function getPutParams() {
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
	/** @var array */
	protected function getPatchParams() {
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
	/** @var array */
	protected function getDeleteParams() {
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
	/** @var array */
	protected function getHeaders() {
		if (!isset($this->properties['Headers'])) {
			$this->properties['Headers'] = $this->getAllHeaders();
		}
		return $this->properties['Headers'];
	}
	/** @var string */
	protected function getInput() {
		if (!isset($this->properties['Input'])) {
			$this->properties['Input'] = $this->getRawInput();
		}
		return $this->properties['Input'];
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
