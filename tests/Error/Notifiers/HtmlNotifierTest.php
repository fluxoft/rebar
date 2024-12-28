<?php

namespace Fluxoft\Rebar\Error\Notifiers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HtmlNotifierTest extends TestCase {
	public function testNotifyVerboseTrue() {
		/** @var HtmlNotifier|MockObject $htmlNotifier */
		$htmlNotifier = $this->getMockBuilder(HtmlNotifier::class)
			->disableOriginalConstructor()
			->onlyMethods(['setHeader', 'outputHtml'])
			->getMock();

		$exception = new \Exception('Test Exception');

		$expectedOutput = "<h1>An error occurred</h1>";
		$expectedOutput .= "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
		$expectedOutput .= "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";

		$htmlNotifier
			->expects($this->once())
			->method('setHeader');
		$htmlNotifier
			->expects($this->once())
			->method('outputHtml')
			->with($expectedOutput);

		$htmlNotifier->__construct(true);
		$htmlNotifier->Notify($exception);
	}

	public function testNotifyVerboseFalse() {
		/** @var HtmlNotifier|MockObject $htmlNotifier */
		$htmlNotifier = $this->getMockBuilder(HtmlNotifier::class)
			->disableOriginalConstructor()
			->onlyMethods(['setHeader', 'outputHtml'])
			->getMock();

		$exception = new \Exception('Test Exception');

		$expectedOutput = "<h1>An error occurred</h1>";
		$expectedOutput .= "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";

		$htmlNotifier
			->expects($this->once())
			->method('setHeader');
		$htmlNotifier
			->expects($this->once())
			->method('outputHtml')
			->with($expectedOutput);

		$htmlNotifier->__construct(false);
		$htmlNotifier->Notify($exception);
	}

	public function testConstructorSetsVerbose() {
		$htmlNotifier = new HtmlNotifier(true);
		$this->assertTrue($this->getPrivateProperty($htmlNotifier, 'verbose'));

		$htmlNotifier = new HtmlNotifier(false);
		$this->assertFalse($this->getPrivateProperty($htmlNotifier, 'verbose'));
	}

	private function getPrivateProperty($object, $property) {
		$reflection = new \ReflectionClass($object);
		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		return $property->getValue($object);
	}
}
