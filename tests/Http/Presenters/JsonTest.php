<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase {
	/** @var MockObject|Response */
	private $responseObserver;

	protected function setup():void {
		$this->responseObserver = $this->getMockBuilder('Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->responseObserver);
	}

	/**
	 * @param $data
	 * @param $callback
	 * @dataProvider renderProvider
	 */
	public function testRender($data, $callback = false) {
		$presenter = new JsonMock($callback);

		$expectedJson = $presenter->PublicJsonEncode($data);
		if ($callback) {
			$expectedType = 'text/javascript;charset=utf-8';
			$expectedBody = $callback.'('.$expectedJson.');';
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
				$this->EqualTo('Body'),
				$this->EqualTo($expectedBody)
			);

		$presenter->Render($this->responseObserver, $data);
	}
	public function renderProvider() {
		$simpleObject              = new \stdClass();
		$simpleObject->propertyOne = "valueOne";
		$simpleObject->propertyTwo = "valueTwo";

		$simpleArray = [
			'foo' => 'bar'
		];
		return [
			'empty' => [
				'data' => [],
				'callback' => false
			],
			'simple' => [
				'data' => ['one', 'two', 'three'],
				'callback' => false
			],
			'mixed' => [
				'data' => [
					'foo' => 'bar',
					'object' => $simpleObject,
					'array' => $simpleArray,
					'booleanTrue' => true,
					'booleanFalse' => false,
					'nullValue' => null
				],
				'callback' => false
			],
			'emptyCallback' => [
				'data' => [],
				'callback' => 'empty'
			],
			'simpleCallback' => [
				'data' => ['one', 'two', 'three'],
				'callback' => 'simple'
			],
			'mixedCallback' => [
				'data' => [
					'foo' => 'bar',
					'object' => $simpleObject,
					'array' => $simpleArray,
					'booleanTrue' => true,
					'booleanFalse' => false,
					'nullValue' => null
				],
				'callback' => 'mixed'
			]
		];
	}
	public function testSetCallback() {
		$presenter = new JsonMock();

		$simpleObject              = new \stdClass();
		$simpleObject->propertyOne = "valueOne";
		$simpleObject->propertyTwo = "valueTwo";

		$simpleArray = [
			'foo' => 'bar'
		];

		$data = [
			'foo' => 'bar',
			'object' => $simpleObject,
			'array' => $simpleArray,
			'booleanTrue' => true,
			'booleanFalse' => false,
			'nullValue' => null
		];
		$presenter->SetCallback('mixed');

		$expectedJson = $presenter->PublicJsonEncode($data);
		$expectedType = 'text/javascript;charset=utf-8';
		$expectedBody = 'mixed('.$expectedJson.');';


		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-type', $expectedType);
		$this->responseObserver
			->expects($this->once())
			->method('__set')
			->with(
				$this->EqualTo('Body'),
				$this->EqualTo($expectedBody)
			);

		$presenter->Render($this->responseObserver, $data);
	}
}

// @codingStandardsIgnoreStart
class JsonMock extends Json {
	// @codingStandardsIgnoreEnd

	public function PublicJsonEncode(array $data) {
		return parent::jsonEncode($data);
	}
}
