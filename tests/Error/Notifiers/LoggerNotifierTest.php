<?php

namespace Fluxoft\Rebar\Tests\Error\Notifiers;

use Fluxoft\Rebar\Error\Notifiers\LoggerNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerNotifierTest extends TestCase {
	public function testNotifyLogsException() {
		$exception = new \Exception('Test exception message', 123);

		/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $mockLogger */
		$mockLogger = $this->createMock(LoggerInterface::class);

		$mockLogger->expects($this->once())
			->method('error')
			->with(
				$exception->getMessage(),
				['exception' => $exception]
			);

		$loggerNotifier = new LoggerNotifier($mockLogger);
		$loggerNotifier->Notify($exception);
	}

	public function testNotifyHandlesLoggerFailureGracefully() {
		$exception = new \Exception('Test exception message', 123);

		/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $mockLogger */
		$mockLogger = $this->createMock(LoggerInterface::class);

		// Simulate an error during logging
		$mockLogger->expects($this->once())
			->method('error')
			->willThrowException(new \RuntimeException('Logger failure'));

		$loggerNotifier = new LoggerNotifier($mockLogger);

		// Verify that Notify does not throw an exception even if the logger fails
		$loggerNotifier->Notify($exception);
	}
}
