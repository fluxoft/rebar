<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {
	public function testDefaultSend() {
		$mockResponse = new MockResponse();

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(200),
				'Content-type: text/html'
			],
			'body' => ''
		];
		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testAddHeader() {
		$mockResponse = new MockResponse();

		$mockResponse->AddHeader('foo', 'bar');

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(200),
				'Content-type: text/html',
				'foo: bar'
			],
			'body' => ''
		];
		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testGetSetStatusAndBody() {
		$mockResponse = new MockResponse();

		$mockResponse->Status        = 410;
		$mockResponse->StatusMessage = 'It Gone Bruh';
		$mockResponse->Body          = 'Ain\'t Nevuh Comin\' Back';
		$this->assertEquals(410, $mockResponse->Status);
		$this->assertEquals('It Gone Bruh', $mockResponse->StatusMessage);
		$this->assertEquals('Ain\'t Nevuh Comin\' Back', $mockResponse->Body);

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(410),
				'Content-type: text/html'
			],
			'body' => 'Ain\'t Nevuh Comin\' Back'
		];
		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testSetNonExistent() {
		$mockResponse = new MockResponse();

		$this->expectException('InvalidArgumentException');

		$mockResponse->NonExistent = 'blah';
	}
	public function testGetNonExistent() {
		$mockResponse = new MockResponse();

		$this->expectException('InvalidArgumentException');

		$nonExistent = $mockResponse->NonExistent;
		unset($nonExistent);
	}
	public function testInvalidStatus() {
		$mockResponse = new MockResponse();

		// Temporarily override the error handler to catch the warning
		$triggeredError = null;
		set_error_handler(function ($errno, $errstr) use (&$triggeredError) {
			$errno          = null; // Unused
			$triggeredError = $errstr;
			return true; // Prevent PHP from displaying the error
		});

		$mockResponse->Status = 666;

		restore_error_handler();

		// Assert that the warning message is as expected
		$this->assertNotNull($triggeredError);
		$this->assertMatchesRegularExpression(
			'/Invalid status code 666 set on Response object/',
			$triggeredError ?? ''
		);
	}
	public function testHalt() {
		$mockResponse = new MockResponse();

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(404),
				'Content-type: text/html'
			],
			'body' => 'It gone...'
		];
		
		$mockResponse->Halt(404, 'It gone...');
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testHaltWithMessage() {
		$mockResponse = new MockResponse();

		$mockResponse->StatusMessage = 'Like so missing'; // set this here so that it matches when we check the sent headers

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(404),
				'Content-type: text/html'
			],
			'body' => 'It gone...'
		];

		$mockResponse->Halt(404, 'It gone...', 'Like so missing');
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testRedirect() {
		$mockResponse = new MockResponse();

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(302),
				'Content-type: text/html',
				'Location: /someplaceElse'
			],
			'body' => ''
		];

		$mockResponse->Redirect('/someplaceElse');
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testRedirectPermanent() {
		$mockResponse = new MockResponse();

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(301),
				'Content-type: text/html',
				'Location: /someplaceElse'
			],
			'body' => ''
		];

		$mockResponse->Redirect('/someplaceElse', true);
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testSetHeadersThrowsException() {
		$mockResponse = new MockResponse();
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Headers is read-only');
	
		$mockResponse->Headers = ['Content-type' => 'application/json'];
	}	
}

// @codingStandardsIgnoreStart
class MockResponse extends Response {
	// @codingStandardsIgnoreEnd

	public function ExposeGetHttpHeader($status) {
		return $this->getHttpHeader($status);
	}

	private $sent = null;
	public function Send(): void {
		$headers   = [];
		$headers[] = $this->getHttpHeader($this->Status);
		foreach ($this->Headers as $type => $content) {
			$headers[] = "$type: $content";
		}
		$this->sent = [
			'headers' => $headers,
			'body' => $this->Body
		];
	}
	public function GetSent() {
		return $this->sent;
	}
}
