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
		$this->startSession();
		parent::__construct($this->superGlobalSession());
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function Set($key, $value) {
		$this->setSession($key, $value);
		parent::Set($key, $value);
	}

	/**
	 * @param $key
	 */
	public function Delete($key) {
		$this->unsetSession($key);
		parent::Delete($key);
	}

	// @codeCoverageIgnoreStart
	protected function superGlobalSession() {
		return $_SESSION;
	}
	protected function startSession() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}
	protected function setSession($key, $value) {
		$_SESSION[$key] = $value;
	}
	protected function unsetSession($key) {
		unset($_SESSION[$key]);
	}
	// @codeCoverageIgnoreEnd
}
