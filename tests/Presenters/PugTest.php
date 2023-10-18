<?php

namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;
use Pug\Pug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
		$presenter = new \Fluxoft\Rebar\Presenters\Pug($this->pugObserver);

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
		$presenter = new \Fluxoft\Rebar\Presenters\Pug($this->pugObserver);

		$this->expectException('InvalidArgumentException');

		$presenter->NonExistent = 'will fail';

		unset($presenter);
	}
	public function testGetNonExistentProperty() {
		$presenter = new \Fluxoft\Rebar\Presenters\Pug($this->pugObserver);

		$this->expectException('InvalidArgumentException');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
