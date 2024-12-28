<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmartyPresenterTest extends TestCase {
	/** @var Response|MockObject */
	private $responseObserver;
	/** @var MockObject|\Smarty */
	private $smartyObserver;

	protected function setup():void {
		$this->responseObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->smartyObserver   = $this->getMockBuilder('\Smarty')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->responseObserver);
		unset($this->smartyObserver);
	}

	/**
	 * @param $templatePath
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider renderProvider
	 */
	public function testRender($templatePath, $template, $layout, $data) {
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
			->with($renderTemplate);

		$presenter->Render($this->responseObserver, $data);
	}
	public function renderProvider() {
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
