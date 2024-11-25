<?php

namespace Fluxoft\Rebar\Http;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {
	/** @var MockObject|Environment */
	private $environmentMock;

	protected function setup():void {
		$this->environmentMock = $this->getMockBuilder('Fluxoft\Rebar\Http\Environment')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->environmentMock);
	}

	/**
	 * @param array $headers
	 * @param array $serverParams
	 * @param array $getParams
	 * @param array $postParams
	 * @param array $putParams
	 * @param array $patchParams
	 * @param array $deleteParams
	 * @param string $input
	 * @param array $expectedGetParams
	 * @param array $expectedPostParams
	 * @param array $expectedPutParams
	 * @param array $expectedPatchParams
	 * @param array $expectedDeleteParams
	 * @param string $expectedMethod
	 * @param string $expectedProtocol
	 * @param string $expectedHost
	 * @param string $expectedPort
	 * @param string $expectedURL
	 * @param string $expectedURI
	 * @param string $expectedPath
	 * @param string $expectedRemoteIP
	 * @param string $expectedBody
	 * @dataProvider requestProvider
	 */
	public function testRequest(
		array $headers,
		array $serverParams,
		array $getParams,
		array $postParams,
		array $putParams,
		array $patchParams,
		array $deleteParams,
		string $input,
		array $expectedGetParams,
		array $expectedPostParams,
		array $expectedPutParams,
		array $expectedPatchParams,
		array $expectedDeleteParams,
		string $expectedMethod,
		string $expectedProtocol,
		string $expectedHost,
		string $expectedPort,
		string $expectedURL,
		string $expectedURI,
		string $expectedPath,
		string $expectedRemoteIP,
		string $expectedBody
	) {
		$request = new Request($this->environmentMock);

		$this->environmentMock
			->expects($this->any())
			->method('__get')
			->willReturnMap([
				['Headers', $headers],
				['ServerParams', $serverParams],
				['GetParams', $getParams],
				['PostParams', $postParams],
				['PutParams', $putParams],
				['PatchParams', $patchParams],
				['DeleteParams', $deleteParams],
				['Input', $input]
			]);

		$this->assertEquals(
			array_change_key_case($headers),
			$request->Headers()
		);
		$this->assertEquals(
			array_change_key_case($serverParams),
			$request->Server()
		);
		$this->assertEquals($expectedGetParams, $request->Get());
		$this->assertEquals($expectedPostParams, $request->Post());
		$this->assertEquals($expectedPutParams, $request->Put());
		$this->assertEquals($expectedPatchParams, $request->Patch());
		$this->assertEquals($expectedDeleteParams, $request->Delete());
		$this->assertEquals($expectedMethod, $request->Method);
		$this->assertEquals($expectedProtocol, $request->Protocol);
		$this->assertEquals($expectedHost, $request->Host);
		$this->assertEquals($expectedPort, $request->Port);
		$this->assertEquals($expectedURI, $request->URI);
		$this->assertEquals($expectedURL, $request->URL);
		$this->assertEquals($expectedPath, $request->Path);
		$this->assertEquals($expectedRemoteIP, $request->RemoteIP);
		$this->assertEquals($expectedBody, $request->Body);
	}
	public function requestProvider() {
		return [
			'simpleGet' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'gEt',
					'REQUEST_SCHEME' => 'hTtp',
					'SERVER_NAME' => 'localHOST',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'simpleGetOddPort' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'gEt',
					'REQUEST_SCHEME' => 'hTtp',
					'SERVER_NAME' => 'localHOST',
					'SERVER_PORT' => 8000,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '8000',
				'expectedURL' => 'http://localhost:8000/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'simpleGetHttps' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'gEt',
					'REQUEST_SCHEME' => 'https',
					'SERVER_NAME' => 'localHOST',
					'SERVER_PORT' => 443,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'https',
				'expectedHost' => 'localhost',
				'expectedPort' => '443',
				'expectedURL' => 'https://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'simpleGetHttpsOddPort' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'gEt',
					'REQUEST_SCHEME' => 'https',
					'SERVER_NAME' => 'localHOST',
					'SERVER_PORT' => 4443,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'https',
				'expectedHost' => 'localhost',
				'expectedPort' => '4443',
				'expectedURL' => 'https://localhost:4443/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'post' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'post',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'set' => 'value'
				],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [
					'set' => 'value'
				],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'POST',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'put' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'PUT',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'putOverride' => [
				'headers' => [
					'X-Http-Method-Override' => 'PUT'
				],
				'serverParams' => [
					'REQUEST_METHOD' => 'POST',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [
					'set' => 'value'
				],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [
					'set' => 'value'
				],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'PUT',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'patch' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'PATCH',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'PATCH',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'patchOverride' => [
				'headers' => [
					'X-Http-Method-Override' => 'PATCH'
				],
				'serverParams' => [
					'REQUEST_METHOD' => 'POST',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [],
				'patchParams' => [
					'set' => 'value'
				],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [
					'set' => 'value'
				],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'PATCH',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'delete' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'DELETE',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'deleteOverride' => [
				'headers' => [
					'X-Http-Method-Override' => 'DELETE'
				],
				'serverParams' => [
					'REQUEST_METHOD' => 'POST',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/index.php//foo///bar/',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [],
				'putParams' => [],
				'patchParams' => [],
				'deleteParams' => [
					'set' => 'value'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [
					'set' => 'value'
				],
				'expectedMethod' => 'DELETE',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/index.php/foo/bar',
				'expectedURI' => '/index.php/foo/bar/',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'htaccessGet' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'GET',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/foo/bar',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/foo/bar',
				'expectedURI' => '/foo/bar',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'getQueryString' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'GET',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/foo/bar?foo=bar',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/foo/bar?foo=bar',
				'expectedURI' => '/foo/bar?foo=bar',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '1.2.3.4',
				'expectedBody' => 'simpleGet'
			],
			'privateIP' => [
				'headers' => [],
				'serverParams' => [
					'REQUEST_METHOD' => 'GET',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/foo/bar',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '192.168.1.1'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/foo/bar',
				'expectedURI' => '/foo/bar',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '192.168.1.1',
				'expectedBody' => 'simpleGet'
			],
			'invalidIP' => [
				'headers' => [
					'X-Forwarded-For' => '192.168.1.1'
				],
				'serverParams' => [
					'REQUEST_METHOD' => 'GET',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/foo/bar',
					'SCRIPT_NAME' => '/index.php'
				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'http',
				'expectedHost' => 'localhost',
				'expectedPort' => '80',
				'expectedURL' => 'http://localhost/foo/bar',
				'expectedURI' => '/foo/bar',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => 'invalid',
				'expectedBody' => 'simpleGet'
			],
			'forwarded' => [
				'headers' => [
					'X-Forwarded-For' => '192.168.1.2, 24.1.1.1, 34.1.1.1',
					'X-Forwarded-Proto' => 'https',
					'X-Forwarded-Port' => 443
				],
				'serverParams' => [
					'REQUEST_METHOD' => 'GET',
					'REQUEST_SCHEME' => 'http',
					'SERVER_NAME' => 'localhost',
					'SERVER_PORT' => 80,
					'REQUEST_URI' => '/foo/bar',
					'SCRIPT_NAME' => '/index.php',
					'REMOTE_ADDR' => '1.2.3.4',

				],
				'getParams' => [
					'foo' => 'bar'
				],
				'postParams' => [
					'post' => 'notset'
				],
				'putParams' => [
					'put' => 'notset'
				],
				'patchParams' => [
					'patch' => 'notset'
				],
				'deleteParams' => [
					'delete' => 'notset'
				],
				'input' => 'simpleGet',
				'expectedGetParams' => [
					'foo' => 'bar'
				],
				'expectedPostParams' => [],
				'expectedPutParams' => [],
				'expectedPatchParams' => [],
				'expectedDeleteParams' => [],
				'expectedMethod' => 'GET',
				'expectedProtocol' => 'https',
				'expectedHost' => 'localhost',
				'expectedPort' => '443',
				'expectedURL' => 'https://localhost/foo/bar',
				'expectedURI' => '/foo/bar',
				'expectedPath' => '/foo/bar',
				'expectedRemoteIP' => '24.1.1.1',
				'expectedBody' => 'simpleGet'
			]
		];
	}

	/**
	 * @dataProvider unsettablePropertiesProvider
	 */
	public function testUnsettableProperties(string $property, $value) {
		$request = new Request($this->environmentMock);

		$this->expectException(InvalidArgumentException::class);
		$request->$property = $value;
	}
	public function unsettablePropertiesProvider(): array {
		return [
			['Method', 'disallowed_input'],
			['Protocol', 'disallowed_input'],
			['Host', 'disallowed_input'],
			['Port', 'disallowed_input'],
			['URL', 'disallowed_input'],
			['URI', 'disallowed_input'],
			['Path', 'disallowed_input'],
			['RemoteIP', 'disallowed_input'],
			['RawBody', 'disallowed_input']
		];
	}

	public function testGetBodyInitializesRawBody() {
		$this->environmentMock->method('__get')
			->willReturnMap([
				['Input', 'mockedInput']
			]);
	
		$request = new Request($this->environmentMock);
	
		$this->assertEquals('mockedInput', $request->Body);
		$this->assertEquals('mockedInput', $request->RawBody);
	}
	public function testSetBodyDoesNotModifyRawBody() {
		$this->environmentMock->method('__get')
			->willReturnMap([
				['Input', 'initialInput']
			]);
	
		$request = new Request($this->environmentMock);
	
		$request->Body = 'updatedBody';
	
		$this->assertEquals('updatedBody', $request->Body);
		$this->assertEquals('initialInput', $request->RawBody);
	}

	public function testSetAuthenticatedUser() {
		// Create a mock of UserInterface
		$userMock = $this->createMock(\Fluxoft\Rebar\Auth\UserInterface::class);
	
		// Create an instance of the Request
		$request = new Request($this->environmentMock);
	
		// Use Reflection to access the protected method
		$reflection = new \ReflectionClass($request);
		$method = $reflection->getMethod('setAuthenticatedUser');
		$method->setAccessible(true);
	
		// Call the protected method
		$method->invoke($request, $userMock);
	
		// Assert that the property was set correctly
		$this->assertSame($userMock, $request->AuthenticatedUser);
	}	
}
