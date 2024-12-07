<?php

namespace Fluxoft\Rebar\Http;

/**
 * Class ParameterSet
 * @package Fluxoft\Rebar\Http
 */
class ParameterSet {
	protected array $params;

	public function __construct(array $params) {
		$this->params = array_change_key_case($params);
	}

	/**
	 * If a key is not provided, returns all parameters.
	 * If a key is provided, returns the value of that key if it exists, or the default if it does not.
	 * If no default is provided, returns null if the key does not exist.
	 * @param string|null $key
	 * @param mixed|null $default
	 */
	public function Get(?string $key = null, mixed $default = null): mixed {
		if (isset($key)) {
			$key = strtolower($key);
			if (isset($this->params[$key])) {
				return $this->params[$key];
			} else {
				return (isset($default)) ? $default : null;
			}
		} else {
			return $this->params;
		}
	}

	public function Set(string $key, mixed $value): void {
		$this->params[strtolower($key)] = $value;
	}

	public function Delete(string $key): void {
		unset($this->params[$key]);
	}
}
