<?php

namespace Fluxoft\Rebar\Tests\Http\Middleware;

use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Middleware\CookieToBearerMiddleware;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CookieToBearerMiddlewareTest extends TestCase {
	public function testProcessAddsBearerTokenWhenAuthorizationHeaderIsMissing(): void {
		/** @var Request|MockObject $requestMock */
		$requestMock = $this->createMock(Request::class);
		/** @var Response|MockObject $responseMock */
		$responseMock = $this->createMock(Response::class);
		/** @var Cookies|MockObject $cookiesMock */
		$cookiesMock = $this->createMock(Cookies::class);

		// Mock behavior for missing Authorization header
		$requestMock->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->willReturn(null);

		// Mock behavior for access Cookie as magic property
		$requestMock->expects($this->once())
			->method('__get')
			->with('Cookies')
			->willReturn($cookiesMock);

		// Mock behavior for AccessToken cookie
		$cookiesMock->expects($this->once())
			->method('Get')
			->with('AccessToken')
			->willReturn('testAccessToken');

		$next = function ($req, $res) {
			unset($req); // unused
			return $res;
		};

		$middleware = new CookieToBearerMiddleware();
		$result     = $middleware->Process($requestMock, $responseMock, $next);

		$this->assertSame($responseMock, $result);
	}

	public function testProcessDoesNotAddBearerTokenWhenAuthorizationHeaderExists(): void {
		/** @var Request|MockObject $requestMock */
		$requestMock = $this->createMock(Request::class);
		/** @var Response|MockObject $responseMock */
		$responseMock = $this->createMock(Response::class);

		// Mock behavior for existing Authorization header
		$requestMock->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer existingToken');

		// Ensure Cookies are never accessed
		$requestMock->expects($this->never())
			->method('__get');

		$next = function ($req, $res) {
			unset($req); // unused
			return $res;
		};

		$middleware = new CookieToBearerMiddleware();
		$result     = $middleware->Process($requestMock, $responseMock, $next);

		$this->assertSame($responseMock, $result);
	}

	public function testProcessDoesNothingWhenAccessTokenCookieIsMissing(): void {
		/** @var Request|MockObject $requestMock */
		$requestMock = $this->createMock(Request::class);
		/** @var Response|MockObject $responseMock */
		$responseMock = $this->createMock(Response::class);
		/** @var Cookies|MockObject $cookiesMock */
		$cookiesMock = $this->createMock(Cookies::class);

		// Mock behavior for missing Authorization header
		$requestMock->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->willReturn(null);

		// Mock behavior for accessing Cookies as magic property
		$requestMock->expects($this->once())
			->method('__get')
			->with('Cookies')
			->willReturn($cookiesMock);

		// Mock behavior for missing AccessToken cookie
		$cookiesMock->expects($this->once())
			->method('Get')
			->with('AccessToken')
			->willReturn(null);

		$next = function ($req, $res) {
			unset($req); // unused
			return $res;
		};

		$middleware = new CookieToBearerMiddleware();
		$result     = $middleware->Process($requestMock, $responseMock, $next);

		$this->assertSame($responseMock, $result);
	}
}
