<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// phpcs:disable
require_once __DIR__ . '/WebAuthTestSetup.php';
// phpcs:enable

class WebAuth_HandleAuthFailureTest extends TestCase {
	use WebAuthTestSetup;

	public function testHandleAuthFailure(): void {
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		/** @var Request|MockObject $mockRequest */
		$mockRequest = $this->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->onlyMethods(['__get', 'Server'])
			->getMock();

		$mockRequest
			->method('__get')
			->willReturnCallback(function ($key) {
				if ($key === 'Path') {
					return '/test';
				}
				if ($key === 'QueryString') {
					return 'test=1';
				}
				return null;
			});
		$mockRequest
			->method('Server')
			->with('QUERY_STRING', '')
			->willReturn('test=1');

		$expectedLoginUrl = '/auth/login?redirect=%2Ftest%3Ftest%3D1';

		/** @var Response|MockObject $mockResponse */
		$mockResponse = $this->createMock(Response::class);

		$mockResponse
			->expects($this->once())
			->method('Redirect')
			->with($expectedLoginUrl);

		$auth->HandleAuthFailure($mockRequest, $mockResponse);
	}
}
