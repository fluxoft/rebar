<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class TwigPresenterTest extends TestCase {
	/** @var MockObject|Environment */
	private $twigObserver;

	protected function setup():void {
		$this->twigObserver = $this->getMockBuilder('\Twig\Environment')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->twigObserver);
	}

	/**
	 * @param $template
	 * @param $layout
	 * @param $data
	 * @dataProvider formatProvider
	 */
	public function testFormat($template, $data) {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$presenter->Template = $template;
		$this->assertEquals($template, $presenter->Template);

		$renderTemplate = $template;
		$this->twigObserver
			->expects($this->once())
			->method('render')
			->with(
				$renderTemplate,
				$data
			)
			->willReturn('rendered template');

		$formatted = $presenter->Format($data);
		$this->assertEquals('rendered template', $formatted['body']);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/html'], $formatted['headers']);
	}
	public function formatProvider() {
		return [
			'noLayout' => [
				'template' => 'template.html',
				'data' => []
			],
			'withLayout' => [
				'template' => 'template.html',
				'data' => []
			]
		];
	}
	public function testFormatTemplateNotFound() {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$presenter->Template = 'template.html';
		$this->assertEquals('template.html', $presenter->Template);

		$this->twigObserver
			->expects($this->once())
			->method('render')
			->willThrowException(new \Twig\Error\LoaderError('Template not found'));

		$formatted = $presenter->Format([]);
		$this->assertEquals('Template not found: template.html', $formatted['body']);
		$this->assertEquals(404, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
	}
	public function testFormatTemplateSyntaxError() {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$presenter->Template = 'template.html';
		$this->assertEquals('template.html', $presenter->Template);

		$this->twigObserver
			->expects($this->once())
			->method('render')
			->willThrowException(new \Twig\Error\SyntaxError('Template syntax error'));

		$formatted = $presenter->Format([]);
		$this->assertEquals('Template syntax error.', $formatted['body']);
		$this->assertEquals(500, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
	}
	public function testFormatTemplateRuntimeError() {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$presenter->Template = 'template.html';
		$this->assertEquals('template.html', $presenter->Template);

		$this->twigObserver
			->expects($this->once())
			->method('render')
			->willThrowException(new \Twig\Error\RuntimeError('Template runtime error'));

		$formatted = $presenter->Format([]);
		$this->assertEquals('Template runtime error.', $formatted['body']);
		$this->assertEquals(500, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
	}
	public function testSetNonExistentProperty() {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$this->expectException(PropertyNotFoundException::class);

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new TwigPresenter(
			$this->twigObserver
		);

		$this->expectException(PropertyNotFoundException::class);

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
}
