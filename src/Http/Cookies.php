<?php

namespace Fluxoft\Rebar\Http;

/**
 * Class Cookies
 * @package Fluxoft\Rebar\Http
 */
class Cookies extends ParameterSet {
	/**
	 * @var array
	 */
	protected $settings = [];

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = []) {
		$defaults = [
			'expires' => 0,
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'httponly' => false
		];

		$this->settings = array_merge($defaults, $settings);

		parent::__construct($this->superGlobalCookies());
	}

	/**
	 * @param $key
	 * @param $value
	 * @param int $expires
	 */
	public function Set(
		$key,
		$value,
		$expires = null
	) {
		$expires = (isset($expires)) ? $expires : $this->settings['expires'];
		if ($this->setCookie(
			$key,
			$value,
			$expires,
			$this->settings['path'],
			$this->settings['domain'],
			$this->settings['secure'],
			$this->settings['httponly']
		)) {
			parent::Set($key, $value);
		}
	}

	/**
	 * @param $key
	 */
	public function Delete($key) {
		if ($this->setCookie(
			$key,
			'',
			0,
			$this->settings['path'],
			$this->settings['domain'],
			$this->settings['secure'],
			$this->settings['httponly']
		)) {
			parent::Delete($key);
		}
	}

	/**
	 * @param $key
	 * @param $value
	 * @param $expires
	 * @param $path
	 * @param $domain
	 * @param $secure
	 * @param $httponly
	 * @return bool
	 * @codeCoverageIgnore
	 */
	protected function setCookie(
		$key,
		$value,
		$expires,
		$path,
		$domain,
		$secure,
		$httponly
	) {
		return setcookie(
			$key,
			$value,
			$expires,
			$path,
			$domain,
			$secure,
			$httponly
		);
	}

	protected function superGlobalCookies() {
		return $_COOKIE; // @codeCoverageIgnore
	}
}
