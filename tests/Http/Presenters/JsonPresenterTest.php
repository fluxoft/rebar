<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonPresenterTest extends TestCase {
	/**
	 * @param array $data
	 * @param string|null $callback If set and a non-empty string, the JSON will be wrapped in a callback in the output,
	 *                              otherwise it will be plain JSON. This is to support JSONP, for those of you who like
	 *                              to kick it old school. JSONP uses the text/javascript content type, as opposed to the
	 *                              more modern application/json content type.
	 * @param string $expectedJson
	 * @dataProvider formatProvider
	 */
	public function testFormat(array $data, ?string $callback, string $expectedJson): void {
		$presenter = new JsonPresenter($callback);

		if ($callback !== null) {
			$expectedType = 'text/javascript;charset=utf-8';
			$expectedBody = $callback . '(' . $expectedJson . ');';
		} else {
			$expectedType = 'application/json;charset=utf-8';
			$expectedBody = $expectedJson;
		}

		$formatted = $presenter->Format($data);
		$this->assertEquals($expectedBody, $formatted['body']);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-type' => $expectedType], $formatted['headers']);
	}

	public function formatProvider(): array {
		$simpleObject              = new \stdClass();
		$simpleObject->propertyOne = "valueOne";
		$simpleObject->propertyTwo = "valueTwo";

		$simpleAssocArray   = [
			'foo' => 'bar'
		];
		$simpleIndexedArray = [
			'one', 'two', 'three'
		];

		return [
			'empty' => [
				'data' => [],
				'callback' => null,
				'expectedJson' => '{}'
			],
			'simple' => [
				'data' => $simpleIndexedArray,
				'callback' => null,
				'expectedJson' => '["one","two","three"]'
			],
			'mixed' => [
				'data' => [
					'foo' => 'bar',
					'object' => $simpleObject,
					'assocArray' => $simpleAssocArray,
					'indexedArray' => $simpleIndexedArray,
					'booleanTrue' => true,
					'booleanFalse' => false,
					'nullValue' => null
				],
				'callback' => null,
				// phpcs:ignore Generic.Files.LineLength
				'expectedJson' => '{"foo":"bar","object":{"propertyOne":"valueOne","propertyTwo":"valueTwo"},"assocArray":{"foo":"bar"},"indexedArray":["one","two","three"],"booleanTrue":true,"booleanFalse":false,"nullValue":null}'
			],
			'nestedObjects' => [
				'data' => [
					'parent' => [
						'child' => [
							'key' => 'value',
							'anotherKey' => 123
						]
					]
				],
				'callback' => null,
				'expectedJson' => '{"parent":{"child":{"key":"value","anotherKey":123}}}'
			],
			'mixedCallback' => [
				'data' => [
					'foo' => 'bar',
					'object' => $simpleObject,
					'assocArray' => $simpleAssocArray,
					'indexedArray' => $simpleIndexedArray,
					'booleanTrue' => true,
					'booleanFalse' => false,
					'nullValue' => null
				],
				'callback' => 'mixed',
				// phpcs:ignore Generic.Files.LineLength
				'expectedJson' => '{"foo":"bar","object":{"propertyOne":"valueOne","propertyTwo":"valueTwo"},"assocArray":{"foo":"bar"},"indexedArray":["one","two","three"],"booleanTrue":true,"booleanFalse":false,"nullValue":null}'
			],
			'emptyArrayVsObject' => [
				'data' => [
					'emptyArray' => [],
					'emptyObject' => (object) []
				],
				'callback' => null,
				'expectedJson' => '{"emptyArray":[],"emptyObject":{}}'
			],
			'largeStructure' => [
				'data' => array_fill(0, 3, ['key' => 'value']),
				'callback' => null,
				'expectedJson' => '[{"key":"value"},{"key":"value"},{"key":"value"}]'
			]
		];
	}

	public function testSetCallback(): void {
		$presenter = new JsonPresenter();

		$data = [
			'foo' => 'bar',
			'object' => ['propertyOne' => 'valueOne', 'propertyTwo' => 'valueTwo'],
			'array' => ['foo' => 'bar'],
			'booleanTrue' => true,
			'booleanFalse' => false,
			'nullValue' => null
		];
		$presenter->SetCallback('mixed');

		// phpcs:ignore Generic.Files.LineLength
		$expectedJson = '{"foo":"bar","object":{"propertyOne":"valueOne","propertyTwo":"valueTwo"},"array":{"foo":"bar"},"booleanTrue":true,"booleanFalse":false,"nullValue":null}';
		$expectedType = 'text/javascript;charset=utf-8';
		$expectedBody = 'mixed(' . $expectedJson . ');';

		$formatted = $presenter->Format($data);
		$this->assertEquals($expectedBody, $formatted['body']);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-type' => $expectedType], $formatted['headers']);
	}

	public function testJsonEncodingException(): void {
		$data = ['invalid' => "\xB1\x31"]; // Invalid UTF-8 sequence to force JsonException

		$presenter = new JsonPresenter();
		$formatted = $presenter->Format($data);

		$this->assertEquals('{"error":"JSON encoding failed"}', $formatted['body']);
		$this->assertEquals(500, $formatted['status']);
		$this->assertEquals(['Content-type' => 'application/json;charset=utf-8'], $formatted['headers']);

		// No exception expected here since it is handled inside the method.
	}
}
