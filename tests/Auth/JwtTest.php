<?php

namespace Fluxoft\Rebar\Auth;

use Firebase\JWT\ExpiredException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase {
	/** @var UserMapperInterface|MockObject */
	private $userMapperObserver;
	/** @var UserInterface|MockObject */
	private $userObserver;
	/** @var \Fluxoft\Rebar\Http\Request|MockObject */
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

	public function testAuthUserNoToken() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode'])
			->getMock();

		$expectedReply          = new Reply();
		$expectedReply->Message = 'No valid AuthToken found in Request.';

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testAuthUserHeadersToken() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode', 'callFirebaseEncode'])
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->will($this->returnValue('Bearer valid-token'));

		$payload         = new \stdClass();
		$payload->userId = 1;

		$jwtMock
			->expects($this->once())
			->method('callFirebaseDecode')
			->with('valid-token')
			->will($this->returnValue($payload));

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue($this->userObserver));

		$jwtMock
			->expects($this->once())
			->method('callFirebaseEncode')
			->will($this->returnValue('valid-token-string'));

		$expectedReply          = new Reply();
		$expectedReply->Auth    = true;
		$expectedReply->Token   = 'valid-token-string';
		$expectedReply->Message = 'Found valid token and logged in';
		$expectedReply->User    = $this->userObserver;

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testAuthUserGetToken() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode', 'callFirebaseEncode'])
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue('valid-token'));

		$payload         = new \stdClass();
		$payload->userId = 1;

		$jwtMock
			->expects($this->once())
			->method('callFirebaseDecode')
			->with('valid-token')
			->will($this->returnValue($payload));

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue($this->userObserver));

		$jwtMock
			->expects($this->once())
			->method('callFirebaseEncode')
			->will($this->returnValue('valid-token-string'));

		$expectedReply          = new Reply();
		$expectedReply->Auth    = true;
		$expectedReply->Token   = 'valid-token-string';
		$expectedReply->Message = 'Found valid token and logged in';
		$expectedReply->User    = $this->userObserver;

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testAuthUserPostToken() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode', 'callFirebaseEncode'])
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Post')
			->with('AuthToken')
			->will($this->returnValue('valid-token'));

		$payload         = new \stdClass();
		$payload->userId = 1;

		$jwtMock
			->expects($this->once())
			->method('callFirebaseDecode')
			->with('valid-token')
			->will($this->returnValue($payload));

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue($this->userObserver));

		$jwtMock
			->expects($this->once())
			->method('callFirebaseEncode')
			->will($this->returnValue('valid-token-string'));

		$expectedReply          = new Reply();
		$expectedReply->Auth    = true;
		$expectedReply->Token   = 'valid-token-string';
		$expectedReply->Message = 'Found valid token and logged in';
		$expectedReply->User    = $this->userObserver;

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testAuthUserExpiredToken() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode', 'callFirebaseEncode'])
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->will($this->returnValue('Bearer expired-token'));

		$payload         = new \stdClass();
		$payload->userId = 1;

		$jwtMock
			->expects($this->once())
			->method('callFirebaseDecode')
			->with('expired-token')
			->willThrowException(new ExpiredException(''));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'The token is expired.';

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testAuthUserNotFound() {
		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseDecode', 'callFirebaseEncode'])
			->getMock();

		$this->requestObserver
			->expects($this->once())
			->method('Headers')
			->with('Authorization')
			->will($this->returnValue('Bearer valid-token'));

		$payload         = new \stdClass();
		$payload->userId = 1;

		$jwtMock
			->expects($this->once())
			->method('callFirebaseDecode')
			->with('valid-token')
			->will($this->returnValue($payload));

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue(null));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'Tried to log in using token but user not found.';

		$authReply = $jwtMock->GetAuthenticatedUser($this->requestObserver);

		$this->assertEquals($expectedReply, $authReply);
	}

	public function testLogin() {
		$username = 'foo';
		$password = 'bar';

		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseEncode'])
			->getMock();
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with($username, $password)
			->will($this->returnValue($this->userObserver));
		$jwtMock
			->expects($this->once())
			->method('callFirebaseEncode')
			->will($this->returnValue('valid-token-string'));

		$expectedReply        = new Reply();
		$expectedReply->Auth  = true;
		$expectedReply->User  = $this->userObserver;
		$expectedReply->Token = 'valid-token-string';

		$loginReply = $jwtMock->Login($username, $password);

		$this->assertEquals($expectedReply, $loginReply);
	}

	public function testLoginNotFound() {
		$username = 'foo';
		$password = 'bar';

		$jwtMock = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Jwt')
			->setConstructorArgs([
				$this->userMapperObserver,
				'secretKey'
			])
			->setMethods(['callFirebaseEncode', 'callFirebaseDecode'])
			->getMock();
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with($username, $password)
			->will($this->returnValue(null));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'User not found';

		$loginReply = $jwtMock->Login($username, $password);

		$this->assertEquals($expectedReply, $loginReply);
	}

	public function testLogout() {
		$jwt           = new Jwt(
			$this->userMapperObserver,
			'secretKey'
		);
		$expectedReply = new Reply();
		$reply         = $jwt->Logout($this->requestObserver);
		$this->assertEquals($expectedReply, $reply);
	}
}
