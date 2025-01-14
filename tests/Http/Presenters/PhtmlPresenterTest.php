<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhtmlPresenterTest extends TestCase {
	public function testRenderWithLayout() {
		$presenter = new PhtmlMock('templatePath/');

		$presenter->Layout   = 'layout.phtml';
		$presenter->Template = 'template.phtml';
		$this->assertEquals('layout.phtml', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);

		$expectedInclude = 'templatePath/layout.phtml';

		$formatted = $presenter->Format([]);

		$this->assertEquals($expectedInclude, $presenter->GetIncluded());
		$this->assertEquals('layout.phtml', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/html'], $formatted['headers']);
		$this->assertEquals('', $formatted['body']);
	}
	public function testRenderWitoutLayout() {
		$presenter = new PhtmlMock('templatePath/');

		$presenter->Layout   = '';
		$presenter->Template = 'template.phtml';
		$this->assertEquals('', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);

		$expectedInclude = 'templatePath/template.phtml';

		$formatted = $presenter->Format([]);

		$this->assertEquals($expectedInclude, $presenter->GetIncluded());
		$this->assertEquals('', $presenter->Layout);
		$this->assertEquals('template.phtml', $presenter->Template);
		$this->assertEquals(200, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/html'], $formatted['headers']);
		$this->assertEquals('', $formatted['body']);
	}
	public function testSetNonExistentProperty() {
		$presenter = new PhtmlMock('templatePath/');

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$presenter->NonExistent = 'will fail';
	}
	public function testGetNonExistentProperty() {
		$presenter = new PhtmlMock('templatePath/');

		$this->expectException(PropertyNotFoundException::class);
		$this->expectExceptionMessage('The property NonExistent does not exist.');

		$nonExistent = $presenter->NonExistent;
		unset($nonExistent);
	}
	public function testNotFound() {
		$presenter = new PhtmlMock('templatePath/');

		$presenter->SetExists(false);

		$formatted = $presenter->Format([]);
		$this->assertEquals(404, $formatted['status']);
		$this->assertEquals(['Content-Type' => 'text/plain'], $formatted['headers']);
		$this->assertEquals('Template not found.', $formatted['body']);
	}
}

// @codingStandardsIgnoreStart
class PhtmlMock extends PhtmlPresenter {
	// @codingStandardsIgnoreEnd

	// This needs to be a no-op so execution isn't interrupted
	private $included;
	protected function includeTemplate(string $include, array $variables): ?string {
		unset($variables); // unused
		$this->included = $include;
		return null;
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
