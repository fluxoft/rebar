<?php

namespace Fluxoft\Rebar\Presenters;

use PHPUnit\Framework\TestCase;

class PhtmlTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $responseObserver;

	protected function setup() {
		$this->responseObserver = $this->getMockBuilder('Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testRenderLayout() {
		$presenter = new PhtmlMock('templatePath/');

		$data = [
			'foo' => 'bar'
		];

		$presenter->Layout   = 'layout.phtml';
		$presenter->Template = 'template.phtml';
		$this->assertEquals('layout.phtml', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);

		$expectedInclude = 'templatePath/layout.phtml';

		$presenter->Render($this->responseObserver, $data);

		$this->assertEquals($expectedInclude, $presenter->GetIncluded());
	}
	public function testRenderTemplate() {
		$presenter = new PhtmlMock('templatePath/');

		$data = [
			'foo' => 'bar'
		];

		$presenter->Layout   = '';
		$presenter->Template = 'template.phtml';
		$this->assertEquals('', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);

		$expectedInclude = 'templatePath/template.phtml';

		$presenter->Render($this->responseObserver, $data);

		$this->assertEquals($expectedInclude, $presenter->GetIncluded());
	}
	public function testSetNonExistentProperty() {
		$presenter = new PhtmlMock('templatePath/');

		$this->expectException('InvalidArgumentException');

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new PhtmlMock('templatePath/');

		$this->expectException('InvalidArgumentException');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
	public function testNotFound() {
		$presenter = new PhtmlMock('templatePath/');

		$presenter->SetExists(false);

		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-Type', 'text/plain');
		$this->responseObserver
			->expects($this->at(1))
			->method('__set')
			->with('Status', 404);
		$this->responseObserver
			->expects($this->at(2))
			->method('__set')
			->with('Body', 'Template not found.');

		$presenter->Render($this->responseObserver, []);
	}
}

// @codingStandardsIgnoreStart
class PhtmlMock extends Phtml {
	// @codingStandardsIgnoreEnd

	// This needs to be a no-op so execution isn't interrupted
	private $included;
	protected function includeTemplate($include) {
		$this->included = $include;
	}
	public function GetIncluded() {
		return $this->included;
	}

	private $exists = true;
	protected function fileExists($include) {
		unset($include);
		return $this->exists;
	}
	public function SetExists($exists) {
		$this->exists = $exists;
	}
}
