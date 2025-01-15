<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebugPresenterTest extends TestCase {
	/**
	 * @param $data
	 * @dataProvider formatProvider
	 */
	public function testFormat($data) {
		$presenter = new DebugMock();

		$expectedBody  = "*** The page's data set: ***\n\n";
		$expectedBody .= $presenter->PublicRenderData($data);
		$expectedBody .= "\n****************************\n";

		$formatted = $presenter->Format($data);
		$this->assertEquals($expectedBody, $formatted['body']);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-type' => 'text/plain'], $formatted['headers']);
	}
	public function formatProvider() {
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
