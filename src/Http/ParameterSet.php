<?php

namespace Fluxoft\Rebar\Http;

class ParameterSet {
	protected $params;

	public function __construct(array $params) {
		$this->params = $params;
	}

	public function Get($var = null, $default = null) {
		if (isset($var)) {
			if (isset($this->params[$var])) {
				return $this->params[$var];
			} else {
				return (isset($default)) ? $default : null;
			}
		} else {
			return $this->params;
		}
	}
}
