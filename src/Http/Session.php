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
	public function Set($key, $value): void {
		$this->setSession($key, $value);
		parent::Set($key, $value);
	}

	/**
	 * @param $key
	 */
	public function Delete($key): void {
		$this->unsetSession($key);
		parent::Delete($key);
	}

	// @codeCoverageIgnoreStart
	protected function superGlobalSession(): array {
		return $_SESSION;
	}
	protected function startSession(): void {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}
	protected function setSession($key, $value): void {
		$_SESSION[$key] = $value;
	}
	protected function unsetSession($key): void {
		unset($_SESSION[$key]);
	}
	// @codeCoverageIgnoreEnd
}
