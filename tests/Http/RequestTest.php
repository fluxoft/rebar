<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $environmentMock;

	protected function setup() {
		$this->environmentMock = $this->getMockBuilder('Fluxoft\Rebar\Http\Environment')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
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
			->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('Headers'))
			->will($this->returnValue($headers));
		$this->environmentMock
			->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ServerParams'))
			->will($this->returnValue($serverParams));
		$this->environmentMock
			->expects($this->at(2))
			->method('__get')
			->with($this->equalTo('GetParams'))
			->will($this->returnValue($getParams));
		$this->environmentMock
			->expects($this->at(3))
			->method('__get')
			->with($this->equalTo('PostParams'))
			->will($this->returnValue($postParams));
		$this->environmentMock
			->expects($this->at(4))
			->method('__get')
			->with($this->equalTo('PutParams'))
			->will($this->returnValue($putParams));
		$this->environmentMock
			->expects($this->at(5))
			->method('__get')
			->with($this->equalTo('PatchParams'))
			->will($this->returnValue($patchParams));
		$this->environmentMock
			->expects($this->at(6))
			->method('__get')
			->with($this->equalTo('DeleteParams'))
			->will($this->returnValue($deleteParams));
		$this->environmentMock
			->expects($this->at(7))
			->method('__get')
			->with($this->equalTo('Input'))
			->will($this->returnValue($input));

		$this->assertEquals($headers, $request->Headers());
		$this->assertEquals($serverParams, $request->Server());
		$this->assertEquals($expectedGetParams, $request->Get());
		$this->assertEquals($expectedPostParams, $request->Post());
		$this->assertEquals($expectedPutParams, $request->Put());
		$this->assertEquals($expectedPatchParams, $request->Patch());
		$this->assertEquals($expectedDeleteParams, $request->Delete());
		$this->assertEquals($expectedMethod, $request->Method);
		$this->assertEquals($expectedProtocol, $request->Protocol);
		$this->assertEquals($expectedHost, $request->Host);
		$this->assertEquals($expectedPort, $request->Port);
		$this->assertEquals($expectedURL, $request->URL);
		$this->assertEquals($expectedURI, $request->URI);
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

	public function testUnsettableProperties() {
		/** @var Request $request */
		$request = new Request($this->environmentMock);

		$this->expectException('\InvalidArgumentException');
		$request->Method = '1.1.1.1';
	}
}
