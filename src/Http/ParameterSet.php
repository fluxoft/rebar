<?php

namespace Fluxoft\Rebar\Http;

/**
 * Class ParameterSet
 * @package Fluxoft\Rebar\Http
 */
class ParameterSet {
	protected $params;

	public function __construct(array $params) {
		$this->params = array_change_key_case($params);
	}

	public function Get($key = null, $default = null) {
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

	public function Set($key, $value) {
		$this->params[strtolower($key)] = $value;
	}

	public function Delete($key) {
		unset($this->params[$key]);
	}
}
