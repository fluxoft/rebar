<?php

namespace Rest;

use Fluxoft\Rebar\Rest\Error;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase {
	public function testErrorMessageOnly() {
		$error = new Error('message');

		$this->assertEquals(0, $error->Code);
		$this->assertEquals('message', $error->Message);
		$this->assertNull($error->Extra);
		$this->assertNull($error->Exception);
	}
	public function testError() {
		$exception = new \Exception('exception');
		$error     = new Error(
			1,
			'message',
			'extra',
			$exception
		);

		$formattedException = [
			'Code' => $exception->getCode(),
			'Message' => $exception->getMessage(),
			'Line' => $exception->getLine(),
			'File' => $exception->getFile(),
			'Trace' => $exception->getTraceAsString()
		];
		$this->assertEquals(1, $error->Code);
		$this->assertEquals('message', $error->Message);
		$this->assertEquals('extra', $error->Extra);
		$this->assertEquals($formattedException, $error->Exception);
	}
}
