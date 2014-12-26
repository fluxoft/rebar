<?php

namespace Fluxoft\Rebar\Http;

/**
 * Class Session
 * @package Fluxoft\Rebar\Http
 */
class Session extends ParameterSet {
	/**
	 * Starts a session if not started and fills the params array with the
	 * values of the $_SESSION superglobal array.
	 */
	public function __construct() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		foreach ($_SESSION as $key => $value) {
			$this->params[$key] = $value;
		}
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function Set($key, $value) {
		$_SESSION[$key] = $value;
		parent::Set($key, $value);
	}

	/**
	 * @param $key
	 */
	public function Delete($key) {
		unset($_SESSION[$key]);
		parent::Delete($key);
	}
}
