<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmartyPresenterTest extends TestCase {
	/** @var MockObject|\Smarty */
	private $smartyObserver;

	protected function setup():void {
		$this->smartyObserver = $this->getMockBuilder('\Smarty')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->smartyObserver);
	}

	/**
	 * @param $templatePath
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider formatProvider
	 */
	public function testFormat($templatePath, $template, $layout, $data) {
		$presenter = new SmartyPresenter(
			$this->smartyObserver,
			$templatePath
		);

		$presenter->Template = $template;
		$presenter->Layout   = $layout;
		$this->assertEquals($template, $presenter->Template);
		$this->assertEquals($layout, $presenter->Layout);

		if (strlen($layout)) {
			$this->smartyObserver
				->expects($this->any())
				->method('assign')
				->willReturnMap([
					[$data, null],
					[['templateFile', $templatePath . $template], null]
				]);
			$renderTemplate = $templatePath.$layout;
		} else {
			$this->smartyObserver
				->expects($this->once())
				->method('assign')
				->with($data);
			$renderTemplate = $templatePath.$template;
		}
		$this->smartyObserver
			->expects($this->once())
			->method('fetch')
			->with($renderTemplate)
			->willReturn('rendered template');

		$formatted = $presenter->Format($data);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/html'], $formatted['headers']);
		$this->assertEquals('rendered template', $formatted['body']);
	}
	public function formatProvider() {
		return [
			'noLayout' => [
				'templatePath' => '/templatePath/',
				'template' => 'template.html',
				'layout' => '',
				'data' => []
			],
			'withLayout' => [
				'templatePath' => '/templatePath/',
				'template' => 'template.html',
				'layout' => 'layout.html',
				'data' => []
			]
		];
	}
	public function testProblemRenderingTemplate() {
		$presenter = new SmartyPresenter(
			$this->smartyObserver,
			'/'
		);

		$this->smartyObserver
			->expects($this->once())
			->method('assign')
			->willThrowException(new \Exception('Smarty error'));

		$formatted = $presenter->Format([]);
		$this->assertEquals(404, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
		$this->assertEquals('Problem was encountered while rendering template: Smarty error', $formatted['body']);
	}
	public function testSetNonExistentProperty() {
		$presenter = new SmartyPresenter(
			$this->smartyObserver,
			'/'
		);

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new SmartyPresenter(
			$this->smartyObserver,
			'/'
		);

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
