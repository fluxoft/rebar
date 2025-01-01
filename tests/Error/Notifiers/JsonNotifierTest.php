<?php

namespace Fluxoft\Rebar\Tests\Error\Notifiers;

use Fluxoft\Rebar\Error\Notifiers\JsonNotifier;
use PHPUnit\Framework\TestCase;

class JsonNotifierTest extends TestCase {
	public function testNotifyVerboseTrue() {
		$exception = new \Exception('Test exception message', 123);

		/** @var JsonNotifier|\PHPUnit\Framework\MockObject\MockObject $jsonNotifier */
		$jsonNotifier = $this->getMockBuilder(JsonNotifier::class)
			->onlyMethods(['setHeaders', 'echoJson', 'callExit'])
			->setConstructorArgs([true])
			->getMock();

		$expectedOutput = [
			'error' => true,
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTrace(),
		];

		$jsonNotifier->expects($this->once())
			->method('setHeaders');
		$jsonNotifier->expects($this->once())
			->method('echoJson')
			->with($expectedOutput);
		$jsonNotifier->expects($this->once())
			->method('callExit');

		$jsonNotifier->Notify($exception);
	}

	public function testNotifyVerboseFalse() {
		$exception = new \Exception('Test exception message', 123);

		/** @var JsonNotifier|\PHPUnit\Framework\MockObject\MockObject $jsonNotifier */
		$jsonNotifier = $this->getMockBuilder(JsonNotifier::class)
			->onlyMethods(['setHeaders', 'echoJson', 'callExit'])
			->setConstructorArgs([false])
			->getMock();

		$expectedOutput = [
			'error' => true,
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
		];

		$jsonNotifier->expects($this->once())
			->method('setHeaders');
		$jsonNotifier->expects($this->once())
			->method('echoJson')
			->with($expectedOutput);
		$jsonNotifier->expects($this->once())
			->method('callExit');

		$jsonNotifier->Notify($exception);
	}
}
