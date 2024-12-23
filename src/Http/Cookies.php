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
			'httponly' => true
		];

		$this->settings = array_merge($defaults, $settings);

		parent::__construct($this->superGlobalCookies());
	}

	/**
	 * Set a cookie value, using $expires if given, or the default expires value if not.
	 * Use $overrides to override the default settings, if needed.
	 * @param string $key
	 * @param mixed  $value
	 * @param array  $overrides Settings overrides, valid keys are: expires, path, domain, secure, httponly
	 */
	public function Set(
		string $key,
		mixed  $value,
		array  $overrides = []
	): void {
		// Merge overrides with current settings
		$finalSettings = array_merge($this->settings, $overrides);
		$expires       = $finalSettings['expires'] ?? 0;
		if ($this->setCookie(
			$key,
			$value,
			$expires,
			$finalSettings['path'],
			$finalSettings['domain'],
			$finalSettings['secure'],
			$finalSettings['httponly']
		)) {
			parent::Set($key, $value);
		}
	}

	/**
	 * @param $key
	 */
	public function Delete(string $key): void {
		if ($this->setCookie(
			$key,
			'',
			time() - 3600,
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
