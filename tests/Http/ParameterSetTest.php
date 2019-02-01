<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class ParameterSetTest extends TestCase {
	/**
	 * @param array $params
	 * @dataProvider paramsProvider
	 */
	public function testParameterSet(array $params) {
		$parameterSet = new ParameterSet($params);

		// test getting entire array with no parameters to Get
		$this->assertEquals($params, $parameterSet->Get());

		// test getting default value for non-existent key
		$this->assertEquals('default', $parameterSet->Get('nonExistent', 'default'));

		// test getting each item in passed array
		foreach ($params as $key => $value) {
			$this->assertEquals($value, $parameterSet->Get($key));
		}

		// test set and delete
		$parameterSet->Set('new_test_key', 'new_test_value');
		$this->assertEquals('new_test_value', $parameterSet->Get('new_test_key'));

		$parameterSet->Delete('new_test_key');
		$this->assertEquals(null, $parameterSet->Get('new_test_key'));
	}
	public function paramsProvider() {
		$testClass        = new \stdClass();
		$testClass->brick = 'red';
		return [
			'basic' => [
				[
					'foo' => 'bar'
				]
			],
			'arrayValue' => [
				[
					'foo' => 'bar',
					'array' => [
						'blah' => 'blerg'
					]
				]
			],
			'objectValue' => [
				[
					'foo' => 'bar',
					'obj' => $testClass
				]
			]
		];
	}
	public function testWeirdCaps() {
		$parameterSet = new ParameterSet([]);

		// test set with mixed-case key, get with different casing
		$parameterSet->Set('weIrdCaPsKEy', 'weird_caps_value');
		$this->assertEquals(
			'weird_caps_value',
			$parameterSet->Get('WeirdCapsKey')
		);
	}
}
