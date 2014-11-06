<?php

namespace Fluxoft\Rebar\Http;

/**
 * Class ParameterSet
 * @package Fluxoft\Rebar\Http
 */
class ParameterSet implements \ArrayAccess {
	protected $params;

	public function __construct(array $params) {
		$this->params = $params;
	}

	public function Get($key = null, $default = null) {
		if (isset($key)) {
			if (isset($this->params[$key])) {
				return $this->params[$key];
			} else {
				return (isset($default)) ? $default : 'not set';
			}
		} else {
			return $this->params;
		}
	}

	public function Set($key, $value) {
		$this->params[$key] = $value;
	}

	public function Delete($key) {
		unset($this->params[$key]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset) {
		return isset($this->params[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		return $this->Get($offset);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->Set($offset, $value);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->Delete($offset);
	}
}
