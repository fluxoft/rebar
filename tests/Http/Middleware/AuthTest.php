<?php

namespace Fluxoft\Rebar\Tests\Http\Middleware;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\BasicAuthChallengeException;
use Fluxoft\Rebar\Http\Middleware\Auth;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {
	public function testSetAuthForPath(): void {
		$authConfig = [
			'/admin' => $this->createMock(AuthInterface::class)
		];

		$middleware = new Auth($authConfig);

		$newAuth = $this->createMock(AuthInterface::class);
		$middleware->SetAuthForPath('/secure', $newAuth);

		$reflection = new \ReflectionClass($middleware);
		$authConfigProperty = $reflection->getProperty('authConfig');
		$authConfigProperty->setAccessible(true);

		$actualAuthConfig = $authConfigProperty->getValue($middleware);

		$this->assertSame($newAuth, $actualAuthConfig['/secure']);
		$this->assertSame($authConfig['/admin'], $actualAuthConfig['/admin']);
	}

	public function testProcessWithMatchingAuth(): void {
		$authMock = $this->createMock(AuthInterface::class);
		$authMock->expects($this->once())
			->method('GetAuthenticatedUser')
			->willReturn((object)[
				'Auth' => true,
				'User' => 'authenticatedUser'
			]);

		$authConfig = [
			'/secure' => $authMock
		];

		$request = $this->createMock(Request::class);
		$request->Path = '/secure';

		$response = $this->createMock(Response::class);

		$middleware = new Auth($authConfig);

		$next = function ($req, $res) {
			$this->assertSame('authenticatedUser', $req->AuthenticatedUser);
			return $res;
		};

		$middleware->Process($request, $response, $next);
	}

	public function testProcessWithNoAuth(): void {
		$request = $this->createMock(Request::class);
		$request->Path = '/noauth';

		$response = $this->createMock(Response::class);

		$middleware = new Auth([]);

		$next = function ($req, $res) {
			return $res;
		};

		$this->assertSame($response, $middleware->Process($request, $response, $next));
	}

	public function testProcessWithUnauthorizedAccess(): void {
		$authMock = $this->createMock(AuthInterface::class);
		$authMock->expects($this->once())
			->method('GetAuthenticatedUser')
			->willReturn((object)[
				'Auth' => false
			]);

		$authConfig = [
			'/secure' => $authMock
		];

		$request = $this->createMock(Request::class);
		$request->Path = '/secure';

		$response = $this->createMock(Response::class);
		$response->expects($this->once())
			->method('Halt')
			->with(403, 'Access denied');

		$middleware = new Auth($authConfig);

		$middleware->Process($request, $response, function () {});
	}

	public function testProcessWithBasicAuthChallengeException(): void {
		$authMock = $this->createMock(AuthInterface::class);
		$authMock->expects($this->once())
			->method('GetAuthenticatedUser')
			->willThrowException(new BasicAuthChallengeException('TestRealm', 'Authentication required'));

		$authConfig = [
			'/secure' => $authMock
		];

		/** @var Request $request */
		$request       = $this->createMock(Request::class);
		$request->Path = '/secure';

		/** @var Response|MockObject $response */
		$response = $this->createMock(Response::class);
		$response->expects($this->once())
			->method('AddHeader')
			->with('WWW-Authenticate', 'Basic realm="TestRealm"');
		$response->expects($this->once())
			->method('Halt')
			->with(401, 'Authentication required');

		$middleware = new Auth($authConfig);

		$middleware->Process($request, $response, function () {});
	}
}
