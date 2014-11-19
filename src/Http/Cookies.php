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
	protected $settings = array();

	/**
	 * @param array $settings
	 */
	public function __construct(array $settings = array()) {
		$defaults = array(
			'expires' => 0,
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'httponly' => false
		);

		$this->settings = array_merge($defaults, $settings);

		foreach ($_COOKIE as $key => $value) {
			$this->params[$key] = $value;
		}
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
		if (setcookie(
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
		if (setcookie(
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
}
