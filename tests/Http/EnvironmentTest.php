<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

/**
 * Class EnvironmentTest
 * @package Fluxoft\Rebar\Http
 */
class EnvironmentTest extends TestCase {
	protected function setup() {}

	protected function teardown() {}

	//	/**
	//	 * @dataProvider settingsProvider
	//	 * @param $settings
	//	 */
	//	public function testDefaultEnvironmentSettings(array $settings) {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock($settings);
	//
	//		/*
	//		 * If no settings are given, check the defaults from the Environment class are set.
	//		 */
	//		if (empty($settings)) {
	//			$defaults = [
	//				'method' => 'GET',
	//				'SCRIPT_NAME' => '',
	//				'pathInfo' => '',
	//				'QUERY_STRING' => '',
	//				'SERVER_NAME' => 'localhost',
	//				'SERVER_PORT' => 80,
	//				'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	//				'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
	//				'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	//				'USER_AGENT' => 'Rebar',
	//				'REMOTE_ADDR' => '127.0.0.1',
	//				'rebar.protocol' => 'http',
	//				'rebar.input' => ''
	//			];
	//
	//			$settings = $defaults;
	//		}
	//
	//		// test getters for array and object access and isset()
	//		foreach ($settings as $key => $value) {
	//			$this->assertTrue(isset($env[$key]));
	//			$this->assertEquals($env[$key], $settings[$key]);
	//			$this->assertEquals($env->$key, $settings[$key]);
	//		}
	//	}
	//	public function settingsProvider() {
	//		return [
	//			// do not override the default settings
	//			[
	//				[]
	//			],
	//			// explicitly setting an array of settings
	//			[
	//				[
	//					'method' => 'GET',
	//					'SCRIPT_NAME' => '',
	//					'pathInfo' => '',
	//					'QUERY_STRING' => '',
	//					'SERVER_NAME' => 'test.com',
	//					'SERVER_PORT' => 80,
	//					'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	//					'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
	//					'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	//					'USER_AGENT' => 'Test Agent',
	//					'REMOTE_ADDR' => '123.123.123.123',
	//					'rebar.protocol' => 'https',
	//					'rebar.input' => ''
	//				]
	//			]
	//		];
	//	}
	//
	//	public function testNoClone() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('Fluxoft\Rebar\Http\Exceptions\EnvironmentException');
	//
	//		$clone = clone($env);
	//		unset($clone);
	//	}
	//	public function testNoSetArray() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('\InvalidArgumentException');
	//		$this->expectExceptionMessage('Read-only object.');
	//
	//		$env['method'] = 'blah';
	//	}
	//	public function testNoSetObject() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('\InvalidArgumentException');
	//		$this->expectExceptionMessage('Read-only object.');
	//
	//		$env->method = 'blah';
	//	}
	//	public function testGetUndefinedArray() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('\InvalidArgumentException');
	//		$this->expectExceptionMessage('Value "undefined_var" is not defined.');
	//
	//		$undefined = $env['undefined_var'];
	//		unset($undefined);
	//	}
	//	public function testGetUndefinedObject() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('\InvalidArgumentException');
	//		$this->expectExceptionMessage('Value "undefined_var" is not defined.');
	//
	//		$undefined = $env->undefined_var;
	//		unset($undefined);
	//	}
	//	public function testNoUnset() {
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock();
	//
	//		$this->expectException('\InvalidArgumentException');
	//		$this->expectExceptionMessage('Read-only object.');
	//
	//		unset($env['method']);
	//	}
	//	public function testString() {
	//		$settings = [
	//			'method' => 'GET',
	//			'SCRIPT_NAME' => '',
	//			'pathInfo' => '',
	//			'QUERY_STRING' => '',
	//			'SERVER_NAME' => 'test.com',
	//			'SERVER_PORT' => 80,
	//			'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	//			'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
	//			'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
	//			'USER_AGENT' => 'Test Agent',
	//			'REMOTE_ADDR' => '123.123.123.123',
	//			'rebar.protocol' => 'https',
	//			'rebar.input' => ''
	//		];
	//		/** @var \Fluxoft\Rebar\Http\Environment $env */
	//		$env = Environment::GetMock($settings);
	//
	//		$string = get_class($env) . " object {\n";
	//		foreach ($settings as $key => $value) {
	//			$string .= "  $key: " . $env->$key . "\n";
	//		}
	//		$string .= "}\n";
	//
	//		$this->assertEquals((string) $env, $string);
	//	}

	public function testGetInstance() {
		$env = MockableEnvironment::GetInstance();
	}
}

// @codingStandardsIgnoreStart
class MockableEnvironment extends Environment {
	// @codingStandardsIgnoreEnd
	protected function superGlobalGet() {
		return [];
	}
	protected function superGlobalPost() {
		return [];
	}
	protected function superGlobalRequest() {
		return [];
	}
	protected function superGlobalServer() {
		return [
			'REQUEST_METHOD' => 'GET',
			'REMOTE_ADDR' => '123.123.123.123',
			'REQUEST_URI' => '/requested',
			'SCRIPT_NAME' => '/script',
			'SERVER_NAME' => 'localhost',
			'SERVER_PORT' => 80,
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
			'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
			'HTTP_USER_AGENT' => 'Test Agent',
		];
	}
}
