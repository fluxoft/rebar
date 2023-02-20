<?php

namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class TwigTest extends TestCase {
	/** @var Response|MockObject */
	private $responseObserver;
	/** @var MockObject|Environment */
	private $twigObserver;

	protected function setup():void {
		$this->responseObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->twigObserver     = $this->getMockBuilder('\Twig\Environment')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->responseObserver);
		unset($this->twigObserver);
	}

	/**
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider renderProvider
	 */
	public function testRender($template, $layout, $data) {
		$presenter = new Twig(
			$this->twigObserver
		);

		$presenter->Template = $template;
		$presenter->Layout   = $layout;
		$this->assertEquals($template, $presenter->Template);
		$this->assertEquals($layout, $presenter->Layout);

		if (strlen($layout)) {
			$data['pageTemplate'] = $template;
			$renderTemplate       = $layout;
		} else {
			$renderTemplate = $template;
		}
		$this->twigObserver
			->expects($this->once())
			->method('render')
			->with(
				$renderTemplate,
				$data
			);

		$presenter->Render($this->responseObserver, $data);
	}
	public function renderProvider() {
		return [
			'noLayout' => [
				'template' => 'template.html',
				'layout' => '',
				'data' => []
			],
			'withLayout' => [
				'template' => 'template.html',
				'layout' => 'layout.html',
				'data' => []
			]
		];
	}
	public function testSetNonExistentProperty() {
		$presenter = new Twig(
			$this->twigObserver
		);

		$this->expectException('InvalidArgumentException');

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new Twig(
			$this->twigObserver
		);

		$this->expectException('InvalidArgumentException');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
