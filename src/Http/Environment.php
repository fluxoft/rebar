<?php
namespace Fluxoft\Rebar\Http;

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
		throw new EnvironmentException('Cloning not allowed');
	}
	private function __construct() {}

	/** @var array */
	private $serverParams = null;
	protected function getServerParams() {
		if (!isset($this->serverParams)) {
			$this->serverParams = $this->superGlobalServer();
		}
		return $this->serverParams;
	}
	/** @var array */
	private $getParams = null;
	protected function getGetParams() {
		if (!isset($this->getParams)) {
			$this->getParams = $this->superGlobalGet();
		}
		return $this->getParams;
	}
	/** @var array */
	private $postParams = null;
	protected function getPostParams() {
		if (!isset($this->postParams)) {
			if (strtoupper($this->ServerParams['REQUEST_METHOD']) === 'POST' &&
				!isset($this->Headers['X-HTTP-Method-Override'])
			) {
				$this->postParams = $this->superGlobalPost();
			} elseif (isset($this->Headers['X-HTTP-Method-Override']) &&
				strtoupper($this->Headers['X-HTTP-Method-Override']) === 'POST'
			) {
				$this->postParams = $this->superGlobalPost();
			} else {
				$this->postParams = [];
			}
		}
		return $this->postParams;
	}
	/** @var array */
	private $putParams = null;
	protected function getPutParams() {
		if (!isset($this->putParams)) {
			if (isset($this->Headers['X-HTTP-Method-Override']) &&
				strtoupper($this->Headers['X-HTTP-Method-Override']) === 'PUT'
			) {
				$this->putParams = $this->superGlobalPost();
			} else {
				$this->putParams = [];
			}
		}
		return $this->putParams;
	}
	/** @var array */
	private $patchParams = null;
	protected function getPatchParams() {
		if (!isset($this->patchParams)) {
			if (isset($this->Headers['X-HTTP-Method-Override']) &&
				strtoupper($this->Headers['X-HTTP-Method-Override']) === 'PATCH'
			) {
				$this->patchParams = $this->superGlobalPost();
			} else {
				$this->patchParams = [];
			}
		}
		return $this->patchParams;
	}
	/** @var array */
	private $deleteParams = null;
	protected function getDeleteParams() {
		if (!isset($this->deleteParams)) {
			if (isset($this->Headers['X-HTTP-Method-Override']) &&
				strtoupper($this->Headers['X-HTTP-Method-Override']) === 'DELETE'
			) {
				$this->deleteParams = $this->superGlobalPost();
			} else {
				$this->deleteParams = [];
			}
		}
		return $this->deleteParams;
	}
	/** @var array */
	private $headers = null;
	protected function getHeaders() {
		if (!isset($this->headers)) {
			$this->headers = $this->getAllHeaders();
		}
		return $this->headers;
	}
	/** @var string */
	private $input = null;
	protected function getInput() {
		if (!isset($this->input)) {
			$rawInput = file_get_contents('php://input');
			if ($rawInput === false) {
				$this->input = '';
			}
			$this->input = $rawInput;
		}
		return $this->input;
	}

	protected function superGlobalGet() {
		return $_GET;
	}
	protected function superGlobalPost() {
		return $_POST;
	}
	protected function superGlobalRequest() {
		return $_REQUEST;
	}
	protected function superGlobalServer() {
		return $_SERVER;
	}

	protected function getAllHeaders() {
		if (function_exists('getallheaders')) {
			return getallheaders();
		} else {
			$out = [];
			foreach ($this->superGlobalServer() as $key => $value) {
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

	public function __get($key) {
		$fnName = "get$key";
		if (is_callable([$this, $fnName])) {
			return $this->$fnName();
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
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

	// Iterator interface implementation.
	private $position = 0;
	public function rewind() {
		$this->position = 0;
	}
	public function current() {
		$keys         = array_keys($this->properties);
		$propertyName = $keys[$this->position];
		return $this->$propertyName;
	}
	public function key() {
		$keys = array_keys($this->properties);
		return $keys[$this->position];
	}
	public function next() {
		++$this->position;
	}
	public function valid() {
		return !($this->position > count($this->properties)-1);
	}
}
