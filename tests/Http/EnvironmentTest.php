<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

/**
 * Class EnvironmentTest
 * @package Fluxoft\Rebar\Http
 */
class EnvironmentTest extends TestCase {
	protected function setup() {}

	protected function teardown() {}

	public function testGetInstance() {
		$environment = MockableEnvironment::GetInstance();

		// everything should be blank for this one
		$this->assertEquals([], $environment->ServerParams);
		$this->assertEquals([], $environment->GetParams);
		$this->assertEquals([], $environment->PostParams);
		$this->assertEquals([], $environment->PutParams);
		$this->assertEquals([], $environment->PatchParams);
		$this->assertEquals([], $environment->DeleteParams);
		$this->assertEquals([], $environment->Headers);
		$this->assertEquals('', $environment->Input);
	}
	public function testNotClonable() {
		$environment = MockableEnvironment::GetInstance();

		// cloning should be disallowed
		$this->expectException('Fluxoft\Rebar\Http\Exceptions\EnvironmentException');
		$this->expectExceptionMessage('Cloning not allowed.');
		$clone = clone($environment);
		unset($clone);
	}
	public function testServerParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->ServerParams = ['foo' => 'bar'];
	}
	public function testServerParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->ServerParams);
	}
	public function testGetParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->GetParams = ['foo' => 'bar'];
	}
	public function testGetParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->GetParams);
	}
	public function testPostParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->PostParams = ['foo' => 'bar'];
	}
	public function testPostParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->PostParams);
	}
	public function testPutParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->PutParams = ['foo' => 'bar'];
	}
	public function testPutParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->PutParams);
	}
	public function testPatchParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->PatchParams = ['foo' => 'bar'];
	}
	public function testPatchParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->PatchParams);
	}
	public function testDeleteParamsNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->DeleteParams = ['foo' => 'bar'];
	}
	public function testDeleteParamsNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->DeleteParams);
	}
	public function testHeadersNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->Headers = ['foo' => 'bar'];
	}
	public function testHeadersNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->Headers);
	}
	public function testInputNotSettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		$environment->Input = ['foo' => 'bar'];
	}
	public function testInputNotUnsettable() {
		$environment = MockableEnvironment::GetInstance();

		// properties should not be settable
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Read-only object.');
		unset($environment->Input);
	}

	/**
	 * @param array $serverGlobal
	 * @param array $postGlobal
	 * @param array $expected
	 * @dataProvider postParamsProvider
	 */
	public function testPostParams(
		array $serverGlobal,
		array $postGlobal,
		array $expected
	) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetServerGlobal($serverGlobal);
		$environment->SetPostGlobal($postGlobal);

		$this->assertEquals($expected, $environment->PostParams);
	}
	public function postParamsProvider() {
		return [
			'postMethod' => [
				'serverGlobal' => ['REQUEST_METHOD' => 'POST'],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => ['foo' => 'bar']
			],
			'postMethodOverrideToPost' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => ['foo' => 'bar']
			],
			'postMethodOverrideToPut' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			]
		];
	}

	/**
	 * @param array $serverGlobal
	 * @param array $postGlobal
	 * @param array $expected
	 * @dataProvider putParamsProvider
	 */
	public function testPutParams(
		array $serverGlobal,
		array $postGlobal,
		array $expected
	) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetServerGlobal($serverGlobal);
		$environment->SetPostGlobal($postGlobal);

		$this->assertEquals($expected, $environment->PutParams);
	}
	public function putParamsProvider() {
		return [
			'putMethod' => [
				'serverGlobal' => ['REQUEST_METHOD' => 'PUT'],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPost' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPut' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => ['foo' => 'bar']
			]
		];
	}

	/**
	 * @param array $serverGlobal
	 * @param array $postGlobal
	 * @param array $expected
	 * @dataProvider patchParamsProvider
	 */
	public function testPatchParams(
		array $serverGlobal,
		array $postGlobal,
		array $expected
	) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetServerGlobal($serverGlobal);
		$environment->SetPostGlobal($postGlobal);

		$this->assertEquals($expected, $environment->PatchParams);
	}
	public function patchParamsProvider() {
		return [
			'putMethod' => [
				'serverGlobal' => ['REQUEST_METHOD' => 'PATCH'],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPost' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPut' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => ['foo' => 'bar']
			]
		];
	}

	/**
	 * @param array $serverGlobal
	 * @param array $postGlobal
	 * @param array $expected
	 * @dataProvider deleteParamsProvider
	 */
	public function testDeleteParams(
		array $serverGlobal,
		array $postGlobal,
		array $expected
	) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetServerGlobal($serverGlobal);
		$environment->SetPostGlobal($postGlobal);

		$this->assertEquals($expected, $environment->DeleteParams);
	}
	public function deleteParamsProvider() {
		return [
			'putMethod' => [
				'serverGlobal' => ['REQUEST_METHOD' => 'delete'],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPost' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => []
			],
			'postMethodOverrideToPut' => [
				'serverGlobal' => [
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'delete'
				],
				'postGlobal' => ['foo' => 'bar'],
				'expected' => ['foo' => 'bar']
			]
		];
	}

	/**
	 * @param $input
	 * @param $expected
	 * @dataProvider inputProvider
	 */
	public function testGetInput($input, $expected) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetRawInput($input);

		$this->assertEquals($expected, $environment->Input);
	}
	public function inputProvider() {
		return [
			'falseInput' => [
				'input' => false,
				'expected' => ''
			],
			'emptyInput' => [
				'input' => '',
				'expected' => ''
			],
			'jsonInput' => [
				'input' => '{"foo":"bar"}',
				'expected' => '{"foo":"bar"}'
			]
		];
	}

	/**
	 * @param $serverGlobal
	 * @param $expectedHeaders
	 * @dataProvider headersProvider
	 */
	public function testHeaders($serverGlobal, $expectedHeaders) {
		/** @var MockableEnvironment $environment */
		$environment = MockableEnvironment::GetInstance();
		$environment->Reset();

		$environment->SetServerGlobal($serverGlobal);

		$this->assertEquals($expectedHeaders, $environment->Headers);
	}
	public function headersProvider() {
		return [
			'noHeaders' => [
				'serverGlobal' => [],
				'expectedHeaders' => []
			],
			'fullHeaders' => [
				'serverGlobal' => [
					'REDIRECT_STATUS' => '200',
					'HTTP_HOST' => 'test.com:8123',
					'HTTP_CONNECTION' => 'keep-alive',
					'HTTP_CACHE_CONTROL' => 'max-age=0',
					'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
					// @codingStandardsIgnoreStart
					'HTTP_USER_AGENT' =>
						'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
					// @codingStandardIgnoreEnd
					'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
					'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
					'HTTP_COOKIE' => 'PHPSESSID=04a1pf6lt6vm9tg06tloj4mpi7',
					'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
					'SERVER_SIGNATURE' => '<address>Apache/2.4.18 (Ubuntu) Server at test.com Port 8123</address>',
					'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
					'SERVER_NAME' => 'test.com',
					'SERVER_ADDR' => '10.0.2.15',
					'SERVER_PORT' => '8123',
					'REMOTE_ADDR' => '10.0.2.2',
					'DOCUMENT_ROOT' => '/websites/test.com/www',
					'REQUEST_SCHEME' => 'http',
					'CONTEXT_PREFIX' => '',
					'CONTEXT_DOCUMENT_ROOT' => '/websites/test.com/www',
					'SERVER_ADMIN' => '[no address given]',
					'SCRIPT_FILENAME' => '/websites/test.com/www/index.php',
					'REMOTE_PORT' => '52177',
					'REDIRECT_URL' => '/main/index',
					'GATEWAY_INTERFACE' => 'CGI/1.1',
					'SERVER_PROTOCOL' => 'HTTP/1.1',
					'REQUEST_METHOD' => 'GET',
					'QUERY_STRING' => '',
					'REQUEST_URI' => '/main/index',
					'SCRIPT_NAME' => '/index.php',
					'PATH_INFO' => '/main/index',
					'PATH_TRANSLATED' => 'redirect:/index.php/main/index/index',
					'PHP_SELF' => '/index.php/main/index',
					'REQUEST_TIME_FLOAT' => '1492643399.319',
					'REQUEST_TIME' => '1492643399'
				],
				'expectedHeaders' => [
					'Host' => 'test.com:8123',
					'Connection' => 'keep-alive',
					'Cache-Control' => 'max-age=0',
					'Upgrade-Insecure-Requests' => '1',
					'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
					'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					'Accept-Encoding' => 'gzip, deflate, sdch',
					'Accept-Language' => 'en-US,en;q=0.8',
					'Cookie' => 'PHPSESSID=04a1pf6lt6vm9tg06tloj4mpi7',
				]
			],
			'authorizationHeader' => [
				'serverGlobal' => [
					'HTTP_AUTHORIZATION' => 'Bearer foo.bar'
				],
				'expectedHeaders' => [
					'Authorization' => 'Bearer foo.bar'
				]
			],
			'redirectAuth' => [
				'serverGlobal' => [
					'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer foo.bar'
				],
				'expectedHeaders' => [
					'Authorization' => 'Bearer foo.bar'
				]
			],
			'basicAuth' => [
				'serverGlobal' => [
					'PHP_AUTH_USER' => 'foo',
					'PHP_AUTH_PW' => 'bar'
				],
				'expectedHeaders' => [
					'Authorization' => 'Basic '.base64_encode('foo:bar')
				]
			],
			'basicAuthNoPassword' => [
				'serverGlobal' => [
					'PHP_AUTH_USER' => 'foo'
				],
				'expectedHeaders' => []
			],
			'authDigest' => [
				'serverGlobal' => [
					'PHP_AUTH_DIGEST' => 'digest nonce'
				],
				'expectedHeaders' => [
					'Authorization' => 'digest nonce'
				]
			]
		];
	}
}

// @codingStandardsIgnoreStart
class MockableEnvironment extends Environment {
	// @codingStandardsIgnoreEnd

	public function Reset() {
		$this->serverParams = null;
		$this->postParams   = null;
		$this->putParams    = null;
		$this->patchParams  = null;
		$this->deleteParams = null;
		$this->headers      = null;
		$this->input        = null;
	}

	private $serverGlobal = [];
	public function SetServerGlobal(array $serverParams) {
		$this->serverGlobal = $serverParams;
	}
	private $getGlobal = [];
	public function SetGetGlobal(array $getParams) {
		$this->getGlobal = $getParams;
	}
	private $postGlobal = [];
	public function SetPostGlobal(array $postParams) {
		$this->postGlobal = $postParams;
	}
	private $rawInput;
	public function SetRawInput($input) {
		$this->rawInput = $input;
	}

	/*
	 * Override these as super-globals aren't useful through PHPUnit
	 */
	protected function superGlobalServer() {
		return $this->serverGlobal;
	}
	protected function superGlobalGet() {
		return $this->getGlobal;
	}
	protected function superGlobalPost() {
		return $this->postGlobal;
	}
	/**
	 * Override this to simulate output from php://input
	 */
	protected function getRawInput() {
		return $this->rawInput;
	}
}
