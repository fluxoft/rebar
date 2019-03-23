<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class CookiesTest extends TestCase {
	/**
	 * @param array $settings
	 * @param array $cookieSet
	 * @param $cookieReturn
	 * @dataProvider cookiesProvider
	 */
	public function test($settings = [], $cookieSet = [], $cookieReturn) {
		$cookies = new MockableCookies($settings);

		$cookies->SetCookies($cookieSet);
		$cookies->SetCookieReturn($cookieReturn);

		// test getting entire array with no parameters to Get
		$this->assertEquals($cookieSet, $cookies->Get());

		// test getting default value for non-existent key
		$this->assertEquals('default', $cookies->Get('nonExistent', 'default'));

		foreach ($cookies as $key => $value) {
			$this->assertEquals($value, $cookies->Get($key));
		}

		// test set and delete
		$cookies->Set('new_cookie', 'new_value');
		if ($cookieReturn) {
			$this->assertEquals('new_value', $cookies->Get('new_cookie'));
		} else {
			$this->assertEquals(null, $cookies->Get('new_cookie'));
		}

		$cookies->Delete('new_cookie');
		$this->assertEquals(null, $cookies->Get('new_test_key'));
	}
	public function cookiesProvider() {
		return [
			'blank' => [
				'settings' => [],
				'cookieSet' => [],
				'cookiesReturn' => true
			],
			'failedSet' => [
				'settings' => [],
				'cookieSet' => [],
				'cookiesReturn' => false
			]
		];
	}
}

// @codingStandardsIgnoreStart
class MockableCookies extends Cookies {
	// @codingStandardsIgnoreEnd

	private $cookieReturn = false;
	protected function setCookie(
		$key,
		$value,
		$expires,
		$path,
		$domain,
		$secure,
		$httponly
	) {
		unset($key);
		unset($value);
		unset($expires);
		unset($path);
		unset($domain);
		unset($secure);
		unset($httponly);

		return $this->cookieReturn;
	}
	public function SetCookieReturn($cookieReturn) {
		$this->cookieReturn = $cookieReturn;
	}

	private $cookieSet = [];
	protected function superGlobalCookies() {
		return $this->cookieSet;
	}
	public function SetCookies($cookieSet) {
		$this->cookieSet = $cookieSet;
	}
}
