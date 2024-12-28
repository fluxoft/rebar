<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonPresenterTest extends TestCase {
	/** @var MockObject|Response */
	private $responseObserver;

	protected function setup():void {
		$this->responseObserver = $this->getMockBuilder(Response::class)
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->responseObserver);
	}

	/**
	 * @param array $data
	 * @param string|null $callback If set and a non-empty string, the JSON will be wrapped in a callback in the output,
	 *                              otherwise it will be plain JSON. This is to support JSONP, for those of you who like
	 *                              to kick it old school. JSONP uses the text/javascript content type, as opposed to the
	 *                              more modern application/json content type.
	 * @param string $expectedJson
	 * @dataProvider renderProvider
	 */
	public function testRender(array $data, ?string $callback, string $expectedJson): void {
		$presenter = new JsonPresenter($callback);

		if ($callback !== null) {
			$expectedType = 'text/javascript;charset=utf-8';
			$expectedBody = $callback . '(' . $expectedJson . ');';
		} else {
			$expectedType = 'application/json;charset=utf-8';
			$expectedBody = $expectedJson;
		}

		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-type', $expectedType);
		$this->responseObserver
			->expects($this->once())
			->method('__set')
			->with(
				$this->equalTo('Body'),
				$this->equalTo($expectedBody)
			);

		$presenter->Render($this->responseObserver, $data);
	}

	public function renderProvider(): array {
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

		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-type', $expectedType);

		$this->responseObserver
			->expects($this->once())
			->method('__set')
			->with(
				$this->equalTo('Body'),
				$this->equalTo($expectedBody)
			);

		$presenter->Render($this->responseObserver, $data);
	}

	public function testJsonEncodingException(): void {
		$data = ['invalid' => "\xB1\x31"]; // Invalid UTF-8 sequence to force JsonException

		/** @var MockObject|Response $response */
		$response = $this->createMock(Response::class);

		$response->expects($this->once())
			->method('AddHeader')
			->with('Content-type', 'application/json;charset=utf-8');

		$response->expects($this->exactly(2))
			->method('__set')
			->willReturnMap([
				['Status', 500],
				['Body', '{"error":"JSON encoding failed"}']
			]);

		$response->expects($this->once())
			->method('Send');

		$presenter = new JsonPresenter();
		$presenter->Render($response, $data);

		// No exception expected here since it is handled inside the method.
	}
}
