<?php

namespace Fluxoft\Rebar\Presenters;

use PHPUnit\Framework\TestCase;

class SmartyTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $responseObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $smartyObserver;

	protected function setup() {
		$this->responseObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->smartyObserver = $this->getMockBuilder('\Smarty')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
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
		$presenter = new Smarty(
			$this->smartyObserver,
			$templatePath
		);

		$presenter->Template = $template;
		$presenter->Layout   = $layout;
		$this->assertEquals($template, $presenter->Template);
		$this->assertEquals($layout, $presenter->Layout);

		if (strlen($layout)) {
			$this->smartyObserver
				->expects($this->at(0))
				->method('assign')
				->with($data);
			$this->smartyObserver
				->expects($this->at(1))
				->method('assign')
				->with(
					'templateFile',
					$templatePath.$template
				);
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
		$presenter = new Smarty(
			$this->smartyObserver,
			'/'
		);

		$this->expectException('InvalidArgumentException');

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new Smarty(
			$this->smartyObserver,
			'/'
		);

		$this->expectException('InvalidArgumentException');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
