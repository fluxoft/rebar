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

	/** @var array */
	protected $properties = [
		'ServerParams' => [],
		'GetParams' => [],
		'PostParams' => [],
		'PutParams' => [],
		'PatchParams' => [],
		'DeleteParams' => [],
		'Headers' => [],
		'Input' => ''
	];

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
	public function __clone() {
		throw new EnvironmentException('Cloning not allowed.');
	}
	private function __construct() {}

	/** @var array */
	protected $serverParams = null;
	protected function getServerParams() {
		if (!isset($this->serverParams)) {
			$this->serverParams = $this->superGlobalServer();
		}
		return $this->serverParams;
	}
	/** @var array */
	protected $getParams = null;
	protected function getGetParams() {
		if (!isset($this->getParams)) {
			$this->getParams = $this->superGlobalGet();
		}
		return $this->getParams;
	}
	/** @var array */
	protected $postParams = null;
	protected function getPostParams() {
		if (!isset($this->postParams)) {
			if (isset($this->ServerParams['REQUEST_METHOD']) &&
				strtoupper($this->ServerParams['REQUEST_METHOD']) === 'POST' &&
				!isset($this->Headers['X-Http-Method-Override'])
			) {
				$this->postParams = $this->superGlobalPost();
			} elseif (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'POST'
			) {
				$this->postParams = $this->superGlobalPost();
			} else {
				$this->postParams = [];
			}
		}
		return $this->postParams;
	}
	/** @var array */
	protected $putParams = null;
	protected function getPutParams() {
		if (!isset($this->putParams)) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'PUT'
			) {
				$this->putParams = $this->superGlobalPost();
			} else {
				$this->putParams = [];
			}
		}
		return $this->putParams;
	}
	/** @var array */
	protected $patchParams = null;
	protected function getPatchParams() {
		if (!isset($this->patchParams)) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'PATCH'
			) {
				$this->patchParams = $this->superGlobalPost();
			} else {
				$this->patchParams = [];
			}
		}
		return $this->patchParams;
	}
	/** @var array */
	protected $deleteParams = null;
	protected function getDeleteParams() {
		if (!isset($this->deleteParams)) {
			if (isset($this->Headers['X-Http-Method-Override']) &&
				strtoupper($this->Headers['X-Http-Method-Override']) === 'DELETE'
			) {
				$this->deleteParams = $this->superGlobalPost();
			} else {
				$this->deleteParams = [];
			}
		}
		return $this->deleteParams;
	}
	/** @var array */
	protected $headers = null;
	protected function getHeaders() {
		if (!isset($this->headers)) {
			$this->headers = $this->getAllHeaders();
		}
		return $this->headers;
	}
	/** @var string */
	protected $input = null;
	protected function getInput() {
		if (!isset($this->input)) {
			$this->input = $this->getRawInput();
		}
		return $this->input;
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
