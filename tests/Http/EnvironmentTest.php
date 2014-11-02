<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 11/1/14
 * Time: 6:15 PM
 */

namespace Fluxoft\Rebar\Http;


class EnvironmentTest extends \PHPUnit_Framework_TestCase {
	protected function setup() {}

	protected function teardown() {}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testDefaultEnvironmentSettings(array $settings) {
		/** @var \Fluxoft\Rebar\Http\Environment $env */
		$env = Environment::GetMock($settings);

		foreach ($settings as $key => $value) {
			$this->assertEquals($env[$key], $settings[$key]);
		}
	}
	public function settingsProvider() {
		return array(
			// do not override the default settings
			array(
				array()
			),
			// explicitly setting an array of settings
			array(
				array(
					'method' => 'GET',
					'SCRIPT_NAME' => '',
					'pathInfo' => '',
					'QUERY_STRING' => '',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
					'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
					'USER_AGENT' => 'Rebar',
					'REMOTE_ADDR' => '127.0.0.1',
					'rebar.protocol' => 'http',
					'rebar.input' => ''
				)
			)
		);
	}
}
