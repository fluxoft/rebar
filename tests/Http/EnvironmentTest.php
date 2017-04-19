<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

/**
 * Class EnvironmentTest
 * @package Fluxoft\Rebar\Http
 * @coversDefaultClass \Fluxoft\Rebar\Http\Environment
 */
class EnvironmentTest extends TestCase {
	protected function setup() {}

	protected function teardown() {}

	/**
	 * @dataProvider settingsProvider
	 * @param $settings
	 */
	public function testDefaultEnvironmentSettings(array $settings) {
		/** @var \Fluxoft\Rebar\Http\Environment $env */
		$env = Environment::GetMock($settings);

		/*
		 * If no settings are given, check the defaults from the Environment class are set.
		 */
		if (empty($settings)) {
			$defaults = [
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
			];

			$settings = $defaults;
		}

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
