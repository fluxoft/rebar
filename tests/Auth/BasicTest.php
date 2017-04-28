<?php

namespace Fluxoft\Rebar\Auth;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $userMapperObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $userObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $requestObserver;

	protected function setup() {
		$this->userMapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver       = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->requestObserver    = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
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
			->setMethods(['sendChallenge', 'Login'])
			->getMock();

		$this->requestObserver
			->expects($this->at(0))
			->method('Server')
			->with('PHP_AUTH_USER')
			->will($this->returnValue($phpAuthUser));

		$reply = null;
		if (!isset($phpAuthUser)) {
			$basicMock
				->expects($this->once())
				->method('sendChallenge');
		} else {
			$this->requestObserver
				->expects($this->at(1))
				->method('Server')
				->with('PHP_AUTH_PW')
				->will($this->returnValue(''));
			$basicMock
				->expects($this->once())
				->method('Login')
				->with($phpAuthUser, '');
		}

		$reply = $basicMock->GetAuthenticatedUser($this->requestObserver);
		unset($reply);
	}
	public function authUserProvider () {
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

		$basic = new Basic(
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
		$basic = new Basic(
			$this->userMapperObserver,
			'realm',
			'message'
		);

		$expectedReply = new Reply();
		$reply         = $basic->Logout($this->requestObserver);
		$this->assertEquals($expectedReply, $reply);
	}
}
