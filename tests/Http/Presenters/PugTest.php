<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pug\Pug;

class PugTest extends TestCase {
	/** @var Response|MockObject */
	private Response|MockObject $responseObserver;
	/** @var MockObject|\Pug\Pug */
	private Pug|MockObject $pugObserver;

	protected function setup():void {
		$this->responseObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->pugObserver      = $this->getMockBuilder('\Pug\Pug')
			->onlyMethods(['renderFile'])
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->responseObserver);
		unset($this->pugObserver);
	}

	/**
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider renderProvider
	 */
	public function testRender($template, $data) {
		$presenter = new \Fluxoft\Rebar\Http\Presenters\Pug($this->pugObserver);

		$presenter->Template = $template;
		$this->assertEquals($template, $presenter->Template);

		$this->pugObserver
			->expects($this->once())
			->method('renderFile')
			->with(
				$template,
				['data' => $data]
			);

		$presenter->Render($this->responseObserver, $data);
	}
	public function renderProvider() {
		return [
			'testRender' => [
				'template' => 'template.pug',
				'data' => []
			]
		];
	}
	public function testSetNonExistentProperty() {
		$presenter = new \Fluxoft\Rebar\Http\Presenters\Pug($this->pugObserver);

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$presenter->NonExistent = 'will fail';

		unset($presenter);
	}
	public function testGetNonExistentProperty() {
		$presenter = new \Fluxoft\Rebar\Http\Presenters\Pug($this->pugObserver);

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
