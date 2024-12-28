<?php

namespace Fluxoft\Rebar\Notifiers\Error;

use Fluxoft\Rebar\Error\Notifiers\TextNotifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TextNotifierTest extends TestCase {
	public function testNotifyExceptionVerboseTrue() {
		/** @var TextNotifier|MockObject $textNotifier */
		$textNotifier = $this->getMockBuilder(TextNotifier::class)
			->disableOriginalConstructor()
			->onlyMethods(['setHeaders', 'echoErrorText', 'callExit'])
			->getMock();

		$exception = new \Exception('Test Exception');

		$expectedText  = "******************************\n";
		$expectedText .= "***  Unhandled exception:  ***\n";
		$expectedText .= "******************************\n";
		$expectedText .= "\n";
		$expectedText .= (string) $exception;

		$textNotifier
			->expects($this->once())
			->method('setHeaders');
		$textNotifier
			->expects($this->once())
			->method('echoErrorText')
			->with($expectedText);
		$textNotifier
			->expects($this->once())
			->method('callExit');

		// Set $verbose to true
		$textNotifier->__construct(true);
		$textNotifier->Notify($exception);
	}

	public function testNotifyExceptionVerboseFalse() {
		/** @var TextNotifier|MockObject $textNotifier */
		$textNotifier = $this->getMockBuilder(TextNotifier::class)
			->disableOriginalConstructor()
			->onlyMethods(['setHeaders', 'echoErrorText', 'callExit'])
			->getMock();

		$exception = new \Exception('Test Exception');

		$expectedText  = "******************************\n";
		$expectedText .= "***  Unhandled exception:  ***\n";
		$expectedText .= "******************************\n";
		$expectedText .= "\n";
		$expectedText .= "A critical error occurred. Please contact the system administrator.\n";

		$textNotifier
			->expects($this->once())
			->method('setHeaders');
		$textNotifier
			->expects($this->once())
			->method('echoErrorText')
			->with($expectedText);
		$textNotifier
			->expects($this->once())
			->method('callExit');

		// Set $verbose to false
		$textNotifier->__construct(false);
		$textNotifier->Notify($exception);
	}

	public function testConstructorSetsVerbose() {
		$textNotifier = new TextNotifier(true);
		$this->assertTrue($this->getPrivateProperty($textNotifier, 'verbose'));

		$textNotifier = new TextNotifier(false);
		$this->assertFalse($this->getPrivateProperty($textNotifier, 'verbose'));
	}

	private function getPrivateProperty($object, $property) {
		$reflection = new \ReflectionClass($object);
		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		return $property->getValue($object);
	}
}
