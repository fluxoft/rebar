<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class CorsTest extends \PHPUnit\Framework\TestCase {
	protected $request;
	protected $response;
	protected $cors;

	protected function setUp(): void {
		$this->request  = $this->createMock(Request::class);
		$this->response = new MockResponse();
		$this->cors     = new Cors(['http://allowed-origin.com'], true);
	}

	public function testOptionsRequestReturns200() {
		$this->request
			->method('__get')
			->will($this->returnValueMap([
				['Method', 'OPTIONS']
			]));

		$response = $this->cors->Process($this->request, $this->response, function($req, $res) {
			$req = null;
			return $res;
		});

		$this->assertEquals(200, $response->Status);
		$this->assertEquals('OK', $response->GetCapturedBody());
	}

	public function testAllowedOriginSetsHeaders() {
		$this->request
			->method('Headers')
			->willReturnCallback(function($header) {
				if ($header === 'Origin') {
					return 'http://allowed-origin.com';
				}
				if ($header === 'Access-Control-Request-Headers') {
					return 'X-Custom-Header';
				}
				return null;
			});

		$this->request
			->method('__get')
			->with('Method')
			->willReturn('GET');

		$response = $this->cors->Process($this->request, $this->response, function($req, $res) {
			$req = null;
			return $res;
		});

		$headers = $response->GetCapturedHeaders();
		$this->assertEquals('http://allowed-origin.com', $headers['Access-Control-Allow-Origin']);
		$this->assertEquals('true', $headers['Access-Control-Allow-Credentials']);
	}

	public function testDisallowedOriginThrowsException() {
		$this->request
			->method('Headers')
			->willReturnCallback(function($header) {
				if ($header === 'Origin') {
					return 'http://disallowed-origin.com';
				}
				if ($header === 'Access-Control-Request-Headers') {
					return 'X-Custom-Header';
				}
				return null;
			});
		$this->request
			->method('__get')
			->with('Method')
			->willReturn('GET');

		$this->expectException(CrossOriginException::class);
		$this->cors->Process($this->request, $this->response, function($req, $res) {
			$req = null;
			return $res;
		});
	}

	public function testDisallowedMethodThrowsException() {
		// Instantiate Cors with allowed methods excluding PATCH
		$this->cors = new Cors(['http://allowed-origin.com'], true, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
		$this->request
			->method('Headers')
			->willReturnCallback(function($header) {
				if ($header === 'Origin') {
					return 'http://allowed-origin.com';
				}
				if ($header === 'Access-Control-Request-Headers') {
					return 'X-Custom-Header';
				}
				return null;
			});
		$this->request
			->method('__get')
			->with('Method')
			->willReturn('PATCH');

		$this->expectException(MethodNotAllowedException::class);
		$this->cors->Process($this->request, $this->response, function($req, $res) {
			$req = null;
			return $res;
		});
	}

	public function testOptionsAlwaysIncludedInAllowedMethods() {
		$this->request
			->method('Headers')
			->willReturnCallback(function($header) {
				if ($header === 'Origin') {
					return 'http://allowed-origin.com';
				}
				if ($header === 'Access-Control-Request-Headers') {
					return 'X-Custom-Header';
				}
				return null;
			});
		$this->request
			->method('__get')
			->with('Method')
			->willReturn('GET');

		$response = $this->cors->Process($this->request, $this->response, function($req, $res) {
			$req = null; // unused
			return $res;
		});

		$headers = $response->GetCapturedHeaders();
		$this->assertEquals('http://allowed-origin.com', $headers['Access-Control-Allow-Origin']);
		$this->assertStringContainsString('OPTIONS', $headers['Access-Control-Allow-Methods']);
	}

	public function testOptionsAddedWhenNotInAllowedMethods() {
		// Instantiate Cors with allowed methods excluding OPTIONS
		$this->cors = new Cors(
			['http://allowed-origin.com'],
			true,
			['GET', 'POST', 'DELETE']
		);

		$this->request
			->method('Headers')
			->willReturnCallback(function($header) {
				if ($header === 'Origin') {
					return 'http://allowed-origin.com';
				}
				if ($header === 'Access-Control-Request-Headers') {
					return 'X-Custom-Header';
				}
				return null;
			});
		$this->request
			->method('__get')
			->with('Method')
			->willReturn('OPTIONS');

		/** @var MockResponse $response */
		$response = $this->cors->Process($this->request, $this->response, function ($req, $res) {
			$req = null; // unused
			return $res;
		});

		$headers = $response->GetCapturedHeaders();

		// Assert CORS headers are correctly set
		$this->assertEquals('http://allowed-origin.com', $headers['Access-Control-Allow-Origin']);
		$this->assertEquals('true', $headers['Access-Control-Allow-Credentials']);
		$this->assertStringContainsString('OPTIONS', $headers['Access-Control-Allow-Methods']);

		// Assert OPTIONS request gets a 200 OK response
		$this->assertEquals(200, $response->Status);
		$this->assertEquals('OK', $response->GetCapturedBody());
	}
}

// @codingStandardsIgnoreStart
class MockResponse extends Response {
	private array $capturedHeaders = [];
	private string $capturedBody = '';

	public function AddHeader($type, $content): void {
		$this->capturedHeaders[$type] = $content;
	}

	public function Halt($status, $body, $message = null): void {
		$this->Status = $status;
		$this->Body = $body;
		if (isset($message)) {
			$this->StatusMessage = $message;
		}
		$this->capturedBody = $body;
		// Do not send headers or exit
	}

	public function Send(): void {
		// Capture instead of sending headers or output
		$this->capturedBody = $this->Body;
	}

	public function GetCapturedHeaders(): array {
		return $this->capturedHeaders;
	}

	public function GetCapturedBody(): string {
		return $this->capturedBody;
	}
}
