<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Http\Presenters\PresenterInterface;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {
	public function testDefaultSend() {
		$mockResponse            = new MockResponse();
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetBody('Test Presenter');

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(200),
				'Content-Type: text/html'
			],
			'body' => 'Test Presenter'
		];
		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testDefaultPresenter() {
		$mockResponse = new MockResponse();

		$expectedBody  = "*** The page's data set: ***\n\n";
		$expectedBody .= '';
		$expectedBody .= "\n****************************\n";
		$expectedSent  = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(200),
				'Content-Type: text/plain'
			],
			'body' => $expectedBody
		];

		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testCustomConstructor() {
		$mockResponse            = new MockResponse(404, 'Not Found', ['Content-Type' => 'text/plain', 'X-Test' => 'Test']);
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetBody('Not Found');
		$testPresenter->SetStatus(404);
		$testPresenter->SetHeaders($mockResponse->Headers);

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(404),
				'Content-Type: text/plain',
				'X-Test: Test'
			],
			'body' => 'Not Found'
		];
		$mockResponse->Send();

		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testAddHeader() {
		$mockResponse            = new MockResponse();
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetBody('Test Presenter');
		$mockResponse->AddHeader('foo', 'bar');

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(200),
				'Content-Type: text/html',
				'Foo: bar'
			],
			'body' => 'Test Presenter'
		];
		$mockResponse->Send();
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}

	public function testGetSetStatusAndBody() {
		$mockResponse            = new MockResponse();
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetStatus(410);
		$testPresenter->SetBody('Ain\'t Nevuh Comin\' Back');

		$mockResponse->Status        = 410;
		$mockResponse->StatusMessage = 'It Gone Bruh';
		$mockResponse->Body          = 'Ain\'t Nevuh Comin\' Back';
		$this->assertEquals(410, $mockResponse->Status);
		$this->assertEquals('It Gone Bruh', $mockResponse->StatusMessage);
		$this->assertEquals('Ain\'t Nevuh Comin\' Back', $mockResponse->Body);

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(410),
				'Content-Type: text/html'
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
		$mockResponse            = new MockResponse();
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetBody('It gone...');
		$testPresenter->SetStatus(404);

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(404),
				'Content-Type: text/html'
			],
			'body' => 'It gone...'
		];

		$mockResponse->Halt(404, 'It gone...');
		$this->assertEquals($expectedSent, $mockResponse->GetSent());
	}
	public function testHaltWithMessage() {
		$mockResponse            = new MockResponse();
		$testPresenter           = new TestPresenter();
		$mockResponse->Presenter = $testPresenter;

		$testPresenter->SetBody('It gone...');
		$testPresenter->SetStatus(404);

		$mockResponse->StatusMessage = 'Like so missing'; // set this here so that it matches when we check the sent headers

		$expectedSent = [
			'headers' => [
				$mockResponse->ExposeGetHttpHeader(404),
				'Content-Type: text/html'
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
				'Content-Type: text/html',
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
				'Content-Type: text/html',
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

		$mockResponse->Headers = ['Content-Type' => 'application/json'];
	}

	public function testGetSetClearData() {
		$mockResponse = new MockResponse();

		$mockResponse->AddData('foo', 'bar');
		$mockResponse->AddData('baz', 'qux');
		$this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $mockResponse->GetData());

		$mockResponse->ClearData();
		$this->assertEquals([], $mockResponse->GetData());
	}
}

// @codingStandardsIgnoreStart
class TestPresenter implements PresenterInterface {
	// @codingStandardsIgnoreEnd
	private string $body;
	public function SetBody(string $body) {
		$this->body = $body;
	}
	private int $status = 200;
	public function SetStatus(int $status) {
		$this->status = $status;
	}
	private array $headers = ['Content-Type' => 'text/html'];
	public function SetHeaders(array $headers) {
		$this->headers = $headers;
	}
	public function Format(array $data): array {
		unset($data); // Unused
		return [
			'body' => $this->body,
			'status' => $this->status,
			'headers' => $this->headers
		];
	}
}

// @codingStandardsIgnoreStart
class MockResponse extends Response {
	// @codingStandardsIgnoreEnd
	public function ExposeGetHttpHeader($status) {
		return $this->getHttpHeader($status);
	}

	private $sent = null;
	protected function sendResponse(): void {
		$headers   = [];
		$headers[] = $this->getHttpHeader($this->Status);
		foreach ($this->Headers as $type => $content) {
			$formattedType = ucwords($type, '-');
			$headers[]     = "$formattedType: $content";
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
