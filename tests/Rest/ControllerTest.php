<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Reply;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase {
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var Response|MockObject */
	private $responseObserver;
	/** @var AuthInterface|MockObject */
	private $authObserver;
	/** @var Controller|MockObject */
	private $controllerObserver;

	protected function setup():void {
		$this->requestObserver  = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->responseObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->authObserver     = $this->getMockBuilder('\Fluxoft\Rebar\Auth\AuthInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->controllerObserver = $this->getMockBuilder('\Fluxoft\Rebar\Rest\Controller')
			->setConstructorArgs([
				$this->requestObserver,
				$this->responseObserver,
				$this->authObserver
			])
			->onlyMethods(['set'])
			->getMock();
	}

	protected function teardown():void {
		unset($this->controllerObserver);
		unset($this->authObserver);
		unset($this->responseObserver);
		unset($this->requestObserver);
	}

	/**
	 * @param $exceptionClass
	 * @param $exceptionMessage
	 * @dataProvider handleAuthGetProvider
	 */
	public function testHandleAuthGet($exceptionClass, $exceptionMessage) {
		$this->requestObserver
			->expects($this->any())
			->method('Get')
			->with('callback')
			->will($this->returnValue('callback'));
		$this->requestObserver
			->expects($this->once())
			->method('__get')
			->with('Method')
			->will($this->returnValue('GET'));

		if (!isset($exceptionClass)) {
			$authReply = new Reply();
			$this->authObserver
				->expects($this->once())
				->method('GetAuthenticatedUser')
				->with($this->requestObserver)
				->will($this->returnValue($authReply));
			$this->controllerObserver
				->expects($this->once())
				->method('set')
				->with('auth', $authReply);
		} else {
			/** @var \Exception $exception */
			$exception = new $exceptionClass($exceptionMessage);
			$this->assertInstanceOf($exceptionClass, $exception);

			$this->authObserver
				->expects($this->once())
				->method('GetAuthenticatedUser')
				->with($this->requestObserver)
				->willThrowException($exception);
			$this->controllerObserver
				->expects($this->once())
				->method('set')
				->with('error', $exception->getMessage());

			if ($exceptionClass === '\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException') {
				$this->responseObserver
					->expects($this->once())
					->method('__set')
					->with('Status', 404);
			}
			if ($exceptionClass === '\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException') {
				$this->responseObserver
					->expects($this->once())
					->method('__set')
					->with('Status', 403);
			}
			if ($exceptionClass === '\Exception') {
				$this->responseObserver
					->expects($this->once())
					->method('__set')
					->with('Status', 500);
			}
		}

		$this->controllerObserver->HandleAuth($this->authObserver);
	}
	public function handleAuthGetProvider() {
		return [
			[
				null,
				null
			],
			[
				'\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException',
				'user not found'
			],
			[
				'\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException',
				'user not found'
			],
			[
				'\Exception',
				'unknown'
			]
		];
	}

	/**
	 * @param $body
	 * @param $exceptionClass
	 * @param $exceptionMessage
	 * @dataProvider handleAuthPostProvider
	 */
	public function testHandleAuthPost($body, $exceptionClass, $exceptionMessage) {
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('callback')
			->will($this->returnValue('callback'));
		$this->requestObserver
			->expects($this->any())
			->method('__get')
			->willReturnMap([
				['Method', 'POST'],
				['Body', $body]
			]);

		$bodyArray = json_decode($body, true);

		if (!isset($bodyArray['credentials']) ||
			!isset($bodyArray['credentials']['username']) ||
			!isset($bodyArray['credentials']['password'])
		) {
			$this->responseObserver
				->expects($this->once())
				->method('__set')
				->with('Status', 400);
			$this->controllerObserver
				->expects($this->once())
				->method('set')
				->with(
					'error',
					'A credentials object is required to log in and must contain a username and password'
				);
		} else {
			$username = $bodyArray['credentials']['username'];
			$password = $bodyArray['credentials']['password'];
			$remember = $bodyArray['credentials']['remember'] ?? false;

			if (!isset($exceptionClass)) {
				$expectedReply = new Reply();

				$this->authObserver
					->expects($this->once())
					->method('Login')
					->with(
						$username,
						$password,
						$remember
					)
					->will($this->returnValue($expectedReply));
				$this->controllerObserver
					->expects($this->once())
					->method('set')
					->with('auth', $expectedReply);
			} else {
				/** @var \Exception $exception */
				$exception = new $exceptionClass($exceptionMessage);
				$this->assertInstanceOf($exceptionClass, $exception);

				$this->authObserver
					->expects($this->once())
					->method('Login')
					->with(
						$username,
						$password,
						$remember
					)
					->willThrowException($exception);
				$this->controllerObserver
					->expects($this->once())
					->method('set')
					->with('error', $exception->getMessage());

				if ($exceptionClass === '\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException') {
					$this->responseObserver
						->expects($this->once())
						->method('__set')
						->with('Status', 404);
				}
				if ($exceptionClass === '\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException') {
					$this->responseObserver
						->expects($this->once())
						->method('__set')
						->with('Status', 403);
				}
				if ($exceptionClass === '\Exception') {
					$this->responseObserver
						->expects($this->once())
						->method('__set')
						->with('Status', 500);
				}
			}
		}

		$this->controllerObserver->HandleAuth($this->authObserver);
	}
	public function handleAuthPostProvider() {
		return [
			[
				json_encode([]),
				null,
				null
			],
			[
				json_encode([
					'credentials' => [
						'username' => 'foo'
					]
				]),
				null,
				null
			],
			[
				json_encode([
					'credentials' => [
						'password' => 'bar'
					]
				]),
				null,
				null
			],
			[
				json_encode([
					'credentials' => [
						'username' => 'foo',
						'password' => 'bar'
					]
				]),
				null,
				null
			],
			[
				json_encode([
					'credentials' => [
						'username' => 'foo',
						'password' => 'bar'
					]
				]),
				'\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException',
				'user not found'
			],
			[
				json_encode([
					'credentials' => [
						'username' => 'foo',
						'password' => 'bar'
					]
				]),
				'\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException',
				'user not found'
			],
			[
				json_encode([
					'credentials' => [
						'username' => 'foo',
						'password' => 'bar'
					]
				]),
				'\Exception',
				'unknown'
			]
		];
	}

	public function testHandleAuthDelete() {
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('callback')
			->will($this->returnValue('callback'));
		$this->requestObserver
			->expects($this->any())
			->method('__get')
			->with('Method')
			->will($this->returnValue('DELETE'));
		$this->authObserver
			->expects($this->once())
			->method('Logout')
			->with($this->requestObserver);
		$this->controllerObserver
			->expects($this->once())
			->method('set')
			->with('auth', false);

		$this->controllerObserver->HandleAuth($this->authObserver);
	}

	/**
	 * @param $verb
	 * @param $reply
	 * @dataProvider handleRepositoryProvider
	 */
	public function testHandleRepository($verb, $reply) {
		$repositoryObserver = $this->getMockBuilder('\Fluxoft\Rebar\Rest\RepositoryInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('callback')
			->will($this->returnValue('callback'));
		$this->requestObserver
			->expects($this->any())
			->method('__get')
			->with('Method')
			->will($this->returnValue($verb));

		$expectedReply = new \Fluxoft\Rebar\Rest\Reply();
		$acceptedVerbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

		if (!in_array($verb, $acceptedVerbs)) {
			$expectedReply->Status = 405;
			$expectedReply->Error  = new Error(405, 'Unsupported method');
		} else {
			$repositoryObserver
				->expects($this->once())
				->method(ucwords(strtolower($verb)))
				->with($this->requestObserver, [])
				->will($this->returnValue($reply));
			$expectedReply = $reply;
		}

		if ($expectedReply instanceof \Fluxoft\Rebar\Rest\Reply) {
			$this->responseObserver
				->expects($this->once())
				->method('__set')
				->with('Status', $expectedReply->Status);
		} else {
			$this->responseObserver
				->expects($this->once())
				->method('__set')
				->with('Status', 500);
			$this->controllerObserver
				->expects($this->once())
				->method('set')
				->with('error', new Error(500, 'Bad reply from repository'));
		}

		$this->controllerObserver->HandleRepository(
			$repositoryObserver,
			[]
		);
	}
	public function handleRepositoryProvider() {
		$reply       = new \Fluxoft\Rebar\Rest\Reply();
		$reply->Meta = ['meta' => 'data'];
		$reply->Data = ['data' => 'data'];

		$errorReply        = new \Fluxoft\Rebar\Rest\Reply();
		$errorReply->Error = new Error('foo');
		return [
			[
				'GET',
				$reply
			],
			[
				'GET',
				null
			],
			[
				'GET',
				$errorReply
			],
			[
				'POST',
				$reply
			],
			[
				'PUT',
				$reply
			],
			[
				'PATCH',
				$reply
			],
			[
				'DELETE',
				$reply
			],
			[
				'foo',
				$reply
			]
		];
	}
}
