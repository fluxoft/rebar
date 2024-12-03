<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\BasicAuthChallengeException;
use Fluxoft\Rebar\Http\ParameterSet;
use Fluxoft\Rebar\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicAuthTest extends TestCase {
	/** @var UserMapperInterface|MockObject  */
	private $userMapperObserver;
	/** @var UserInterface|MockObject */
	private $userObserver;
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var ParameterSet|MockObject */
	private $serverParamSet;

	protected function setup(): void {
		$this->userMapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver       = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->serverParamSet     = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
			->getMock();

		$this->requestObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver
			->method('__get')
			->willReturnCallback(function ($key) {
				if ($key === 'Server') {
					return $this->serverParamSet;
				}
				return null;
			});
	}


	protected function teardown(): void {
		unset($this->requestObserver);
		unset($this->userObserver);
		unset($this->userMapperObserver);
		unset($this->serverParamSet);
	}

	/**
	 * @param $phpAuthUser
	 * @dataProvider authUserProvider
	 */
	public function testAuthUser($phpAuthUser) {
		// Mock the ParameterSet::Get method with willReturnCallback
		$this->serverParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) use ($phpAuthUser) {
				if ($key === 'PHP_AUTH_USER') {
					return $phpAuthUser ?? $default;
				}
				if ($key === 'PHP_AUTH_PW') {
					return 'test-password';
				}
				return $default;
			});
	
		// Handle the two cases: when $phpAuthUser is null and when it's provided
		if (!isset($phpAuthUser)) {
			$this->expectException(BasicAuthChallengeException::class);
			$this->expectExceptionMessage('Missing or invalid credentials.');
	
			$basicAuth = new BasicAuth(
				$this->userMapperObserver,
				'realm',
				'Missing or invalid credentials.'
			);
			$basicAuth->GetAuthenticatedUser($this->requestObserver);
		} else {
			/** @var BasicAuth|MockObject $basicAuthMock */
			$basicAuthMock = $this->getMockBuilder(BasicAuth::class)
				->setConstructorArgs([
					$this->userMapperObserver,
					'realm',
					'This should work.'
				])
				->onlyMethods(['Login'])
				->getMock();
	
			// Expect the Login method to be called with correct parameters
			$basicAuthMock
				->expects($this->once())
				->method('Login')
				->with($this->requestObserver, $phpAuthUser, 'test-password');
	
			// Trigger GetAuthenticatedUser to test behavior
			$basicAuthMock->GetAuthenticatedUser($this->requestObserver);
		}
	}
	public function authUserProvider(): array {
		return [
			// Case where PHP_AUTH_USER is not set
			['user' => null],
			// Case where PHP_AUTH_USER is set
			['user' => 'username']
		];
	}

	public function testLogin() {
		$username = 'foo';
		$password = 'bar';

		$basicAuth = new BasicAuth(
			$this->userMapperObserver,
			'realm',
			'message'
		);
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with($username, $password)
			->willReturn($this->userObserver);

		$expectReply       = new Reply();
		$expectReply->Auth = true;
		$expectReply->User = $this->userObserver;

		$reply = $basicAuth->Login($this->requestObserver, $username, $password);

		$this->assertEquals($expectReply, $reply);
	}

	public function testLogout() {
		$basicAuth = new BasicAuth(
			$this->userMapperObserver,
			'realm',
			'message'
		);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Logout is not supported with Basic Auth');

		$basicAuth->Logout($this->requestObserver);
	}
}
