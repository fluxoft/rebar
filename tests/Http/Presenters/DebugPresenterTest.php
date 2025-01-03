<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebugPresenterTest extends TestCase {
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
	 * @dataProvider renderProvider
	 */
	public function testRender($data) {
		$presenter = new DebugMock();

		$expectedBody  = "*** The page's data set: ***\n\n";
		$expectedBody .= $presenter->PublicRenderData($data);
		$expectedBody .= "\n****************************\n";

		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-type', 'text/plain');
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
				'data' => []
			],
			'simple' => [
				'data' => ['one', 'two', 'three']
			],
			'mixed' => [
				'data' => [
					'foo' => 'bar',
					'object' => $simpleObject,
					'array' => $simpleArray
				]
			]
		];
	}
}

// @codingStandardsIgnoreStart
class DebugMock extends DebugPresenter {
	// @codingStandardsIgnoreEnd

	public function PublicRenderData(array $data, $indent = null) {
		return parent::renderData($data, $indent);
	}
}
