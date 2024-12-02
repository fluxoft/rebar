<?php

namespace Fluxoft\Rebar\Auth;

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

	protected function setup():void {
		$this->userMapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver       = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->requestObserver    = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->requestObserver);
		unset($this->userObserver);
		unset($this->userMapperObserver);
	}

	/**
	 * @param $phpAuthUser
	 * @dataProvider authUserProvider
	 */
	public function testAuthUser($phpAuthUser) {
		$basicMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Basic')
			->setConstructorArgs([
				$this->userMapperObserver,
				'realm',
				'message'
			])
			->onlyMethods(['sendChallenge', 'Login'])
			->getMock();

		$this->requestObserver
			->expects($this->any())
			->method('Server')
			->will($this->returnValueMap([
				['PHP_AUTH_USER', null, $phpAuthUser],
				['PHP_AUTH_PW', null, '']
			]));

		if (!isset($phpAuthUser)) {
			$basicMock
				->expects($this->once())
				->method('sendChallenge');
		} else {
			$basicMock
				->expects($this->once())
				->method('Login')
				->with($phpAuthUser, '');
		}

		$reply = $basicMock->GetAuthenticatedUser($this->requestObserver);
		unset($reply);
	}
	public function authUserProvider ():array {
		return [
			[
				'user' => null
			],
			[
				'user' => 'username'
			]
		];
	}

	public function testLogin() {
		$username = 'foo';
		$password = 'bar';

		$basic = new BasicAuth(
			$this->userMapperObserver,
			'realm',
			'message'
		);
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with($username, $password)
			->will($this->returnValue($this->userObserver));

		$expectReply       = new Reply();
		$expectReply->Auth = true;
		$expectReply->User = $this->userObserver;

		$reply = $basic->Login($username, $password);

		$this->assertEquals($expectReply, $reply);
	}

	public function testLogout() {
		$basic = new BasicAuth(
			$this->userMapperObserver,
			'realm',
			'message'
		);

		$expectedReply = new Reply();
		$reply         = $basic->Logout($this->requestObserver);
		$this->assertEquals($expectedReply, $reply);
	}
}
