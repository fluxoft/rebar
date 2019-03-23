<?php

namespace Fluxoft\Rebar\Error;

use PHPUnit\Framework\TestCase;

class BasicNotifierTest extends TestCase {	
	public function testNotifyException() {
		$basicNotifier = $this->getMockBuilder('\Fluxoft\Rebar\Error\BasicNotifier')
			->disableOriginalConstructor()
			->setMethods(['setHeaders', 'echoErrorText', 'callExit'])
			->getMock();
		
		$exception = new \Exception('Test Exception');

		$expectedText  = "******************************\n";
		$expectedText .= "***  Unhandled exception:  ***\n";
		$expectedText .= "******************************\n";
		$expectedText .= "\n";
		$expectedText .= (string) $exception;
		
		$basicNotifier
			->expects($this->once())
			->method('setHeaders');
		$basicNotifier
			->expects($this->once())
			->method('echoErrorText')
			->with($expectedText);
		$basicNotifier
			->expects($this->once())
			->method('callExit');

		$basicNotifier->Notify($exception);
	}
}
