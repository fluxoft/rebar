<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Presenters\PugPresenter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pug\Pug;

class PugPresenterTest extends TestCase {
	/** @var MockObject|\Pug\Pug */
	private Pug|MockObject $pugObserver;

	protected function setup():void {
		$this->pugObserver = $this->getMockBuilder('\Pug\Pug')
			->onlyMethods(['renderFile'])
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->pugObserver);
	}

	/**
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider formatProvider
	 */
	public function testFormat($template, $data) {
		$presenter = new PugPresenter($this->pugObserver, 'templates/');

		$presenter->Template = $template;
		$this->assertEquals($template, $presenter->Template);

		$this->pugObserver
			->expects($this->once())
			->method('renderFile')
			->with(
				'templates/'.$template,
				['data' => $data]
			)
			->willReturn('html body');

		$formatted = $presenter->Format($data);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/html'], $formatted['headers']);
		$this->assertEquals('html body', $formatted['body']);
	}
	public function formatProvider() {
		return [
			'testRender' => [
				'template' => 'template.pug',
				'data' => []
			]
		];
	}
	public function testMissingTemplate() {
		$presenter = new PugPresenter($this->pugObserver, 'templates/');

		$this->pugObserver
			->expects($this->once())
			->method('renderFile')
			->willThrowException(new \Exception('Template not found.'));

		$formatted = $presenter->Format([]);
		$this->assertEquals(404, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
		$this->assertEquals('Template not found.', $formatted['body']);
	}
	public function testSetNonExistentProperty() {
		$presenter = new \Fluxoft\Rebar\Http\Presenters\PugPresenter($this->pugObserver, 'templates/');

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$presenter->NonExistent = 'will fail';

		unset($presenter);
	}
	public function testGetNonExistentProperty() {
		$presenter = new PugPresenter($this->pugObserver, 'templates/');

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
