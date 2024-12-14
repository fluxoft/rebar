<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\BasicAuthChallengeException;
use Fluxoft\Rebar\Auth\Reply;
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

		/** @var AuthInterface $newAuth */
		$newAuth = $this->createMock(AuthInterface::class);
		$middleware->SetAuthForPath($newAuth, '/secure');

		$reflection         = new \ReflectionClass($middleware);
		$authConfigProperty = $reflection->getProperty('authConfig');
		$authConfigProperty->setAccessible(true);

		$actualAuthConfig = $authConfigProperty->getValue($middleware);

		$this->assertSame($newAuth, $actualAuthConfig['/secure']);
		$this->assertSame($authConfig['/admin'], $actualAuthConfig['/admin']);
	}

	public function testProcessWithMatchingAuth(): void {
		$replyMock = $this->createMock(Reply::class);
		$replyMock->method('__get')
			->willReturnMap([
				['Auth', true],
				['User', 'authenticatedUser']
			]);

		$authMock = $this->createMock(AuthInterface::class);
		$authMock->expects($this->once())
			->method('GetAuthenticatedUser')
			->willReturn($replyMock);

		$authConfig = [
			'/secure' => $authMock
		];

		/** @var Request|MockObject $request */
		$request = $this->createMock(Request::class);
		// Mock __get behavior
		$request->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($key) use (&$authMock) {
				switch ($key) {
					case 'Path':
						return '/secure';
					case 'AuthenticatedUser':
						return 'authenticatedUser';
					case 'Auth':
						return $authMock;
					default:
						return null;
				}
			});

		// Mock __set behavior
		$request->expects($this->any())
			->method('__set')
			->willReturnCallback(function ($key, $value) use (&$authMock) {
				switch ($key) {
					case 'Auth':
						$this->assertSame($authMock, $value);
						break;
					case 'AuthenticatedUser':
						$this->assertSame('authenticatedUser', $value);
						break;
				}
			});

		/** @var Response|MockObject $response */
		$response = $this->createMock(Response::class);

		$middleware = new Auth($authConfig);

		$next = function ($req, $res) {
			$this->assertSame('authenticatedUser', $req->AuthenticatedUser);
			return $res;
		};

		$middleware->Process($request, $response, $next);
	}

	public function testProcessWithNoAuth(): void {
		/** @var Request|MockObject $request */
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('__get')
			->with('Path')
			->willReturn('/noauth');

		/** @var Response|MockObject $response */
		$response = $this->createMock(Response::class);

		$middleware = new Auth([]);

		$next = function ($req, $res) {
			unset($req); // unused
			return $res;
		};

		$this->assertSame($response, $middleware->Process($request, $response, $next));
	}

	public function testProcessWithUnauthorizedAccess(): void {
		$auth            = $this->createMock(AuthInterface::class);
		$authReply       = $this->createMock(Reply::class);
		$authReply->Auth = false;

		$auth->expects($this->once())
			->method('GetAuthenticatedUser')
			->willReturn($authReply);

		/** @var Request|MockObject $request */
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('__get')
			->willReturnMap([
				['Path', '/secure']
			]);

		/** @var Response|MockObject $response */
		$response = $this->createMock(Response::class);
		$response->expects($this->once())
			->method('Halt')
			->with(403, 'Access denied')
			->willThrowException(new \Exception('Processing halted'));

		$middleware = new Auth(['/secure' => $auth]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Processing halted');

		$middleware->Process($request, $response, fn() => $this->fail('Next middleware should not be called'));
	}

	public function testProcessWithBasicAuthChallengeException(): void {
		$auth = $this->createMock(AuthInterface::class);
		$auth->method('GetAuthenticatedUser')
			->willThrowException(new BasicAuthChallengeException('Restricted Area', 'Basic Auth Required'));

		/** @var Request|MockObject $request */
		$request = $this->createMock(Request::class);
		$request->expects($this->any())
			->method('__get')
			->willReturnMap([
				['Path', '/secure']
			]);

		/** @var Response|MockObject $response */
		$response = $this->createMock(Response::class);
		$response->expects($this->once())
			->method('Halt')
			->with(401, 'Basic Auth Required')
			->willThrowException(new \Exception('Processing halted'));

		$middleware = new Auth(['/secure' => $auth]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Processing halted');

		$middleware->Process($request, $response, fn() => $this->fail('Next middleware should not be called'));
	}
}
