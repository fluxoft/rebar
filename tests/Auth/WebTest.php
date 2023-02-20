<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Db\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebTest extends TestCase {
	/** @var Db\UserMapper|MockObject */
	private $userMapperObserver;
	/** @var Db\TokenMapper|MockObject */
	private $tokenMapperObserver;
	/** @var Db\User|MockObject */
	private $userObserver;
	/** @var \Fluxoft\Rebar\Http\Request|MockObject */
	private $requestObserver;
	/** @var \Fluxoft\Rebar\Http\Cookies|MockObject */
	private $cookiesObserver;
	/** @var \Fluxoft\Rebar\Http\Session|MockObject */
	private $sessionObserver;

	protected function setup():void {
		$this->userMapperObserver  = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Db\UserMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenMapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Db\TokenMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->userObserver        = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Db\User')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver     = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->cookiesObserver     = $this->getMockBuilder('\Fluxoft\Rebar\Http\Cookies')
			->disableOriginalConstructor()
			->getMock();
		$this->sessionObserver     = $this->getMockBuilder('\Fluxoft\Rebar\Http\Session')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->sessionObserver);
		unset($this->cookiesObserver);
		unset($this->requestObserver);
		unset($this->userObserver);
		unset($this->tokenMapperObserver);
		unset($this->userMapperObserver);
	}

	public function testGetAuthUserSession() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);
		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->willReturnMap([
				['AuthUserId', null, 1],
				['AuthToken', null, 'valid-token']
			]);
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue($this->userObserver));

		$expectedReply          = new Reply();
		$expectedReply->Auth    = true;
		$expectedReply->Token   = 'valid-token';
		$expectedReply->Message = 'Logged in using session';
		$expectedReply->User    = $this->userObserver;

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserSessionUserNotFound() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);
		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(1));
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue(null));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'Tried to log in with session but user not found';

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserNoSessionNoCookieRequestToken() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$token       = new Token(1);
		$checksum    = hash('md5', (string) $token);
		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue($tokenString));
		$this->tokenMapperObserver
			->expects($this->once())
			->method('CheckAuthToken')
			->will($this->returnValue(true));
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue(null));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'Tried to log in using token but user not found. '.$token->UserID;

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserNoToken() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$token       = new Token(1);
		$checksum    = hash('md5', (string) $token);
		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Delete')
			->with('AuthToken');
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AuthUserId'], ['AuthToken']);

		$expectedReply          = new Reply();
		$expectedReply->Message = 'No auth tokens found. Authentication failed.';

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserInvalidToken() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$tokenString = 'bad token string';

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue($tokenString));
		$this->cookiesObserver
			->expects($this->once())
			->method('Delete')
			->with('AuthToken');
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AuthUserId'], ['AuthToken']);

		$expectedReply          = new Reply();
		$expectedReply->Message = 'No auth tokens found. Authentication failed.';

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserValidTokenNotInDb() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$token    = new Token(1);
		$checksum = hash('md5', (string) $token);

		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue($tokenString));
		$this->tokenMapperObserver
			->expects($this->once())
			->method('CheckAuthToken')
			->will($this->returnValue(false));
		$this->cookiesObserver
			->expects($this->once())
			->method('Delete')
			->with('AuthToken');
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AuthUserId'], ['AuthToken']);

		$expectedReply          = new Reply();
		$expectedReply->Message = 'No auth tokens found. Authentication failed.';

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserValidTokenUserNotFound() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$token       = new Token(1);
		$checksum    = hash('md5', (string) $token);
		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue($tokenString));
		$this->tokenMapperObserver
			->expects($this->once())
			->method('CheckAuthToken')
			->will($this->returnValue(true));
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue(null));

		$expectedReply          = new Reply();
		$expectedReply->Message = 'Tried to log in using token but user not found. '.$token->UserID;

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}
	public function testGetAuthUserValidTokenUserFound() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$token       = new Token(1);
		$checksum    = hash('md5', (string) $token);
		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->sessionObserver
			->expects($this->any())
			->method('Get')
			->with('AuthUserId')
			->will($this->returnValue(null));
		$this->cookiesObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue(null));
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->with('AuthToken')
			->will($this->returnValue($tokenString));
		$this->tokenMapperObserver
			->expects($this->once())
			->method('CheckAuthToken')
			->will($this->returnValue(true));
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->will($this->returnValue($this->userObserver));
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Set')
			->withConsecutive(
				['AuthUserId', $token->UserID],
				['AuthToken', $tokenString]
			);
		$this->cookiesObserver
			->expects($this->once())
			->method('Set')
			->with(
				'AuthToken',
				$tokenString,
				$this->logicalAnd(
					$this->isType('integer'),
					$this->greaterThan(0)
				)
			);
		$this->tokenMapperObserver
			->expects($this->once())
			->method('SaveAuthToken');

		$expectedReply          = new Reply();
		$expectedReply->Auth    = true;
		$expectedReply->Token   = $tokenString;
		$expectedReply->Message = 'Found valid token.';
		$expectedReply->User    = $this->userObserver;

		$this->assertEquals(
			$expectedReply, $web->GetAuthenticatedUser($this->requestObserver)
		);
	}

	public function testLoginFail() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$expectedReply = new Reply();

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->will($this->returnValue(null));

		$this->assertEquals(
			$expectedReply, $web->Login('user', 'pass')
		);
	}
	public function testLoginSuccess() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$expectedReply        = new Reply();
		$expectedReply->Auth  = true;
		$expectedReply->User  = $this->userObserver;
		$expectedReply->Token = 'valid-token';

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->will($this->returnValue($this->userObserver));
		$this->userObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue(1));
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Set')
			->withConsecutive(
				['AuthUserId', 1],
				['AuthToken', $this->isType('string')]
			);
		$this->cookiesObserver
			->expects($this->once())
			->method('Set')
			->with(
				'AuthToken',
				$this->isType('string'),
				$this->logicalAnd(
					$this->isType('integer'),
					$this->equalTo(0)
				)
			);
		$this->tokenMapperObserver
			->expects($this->once())
			->method('SaveAuthToken');

		$authReply = $web->Login('user', 'pass');
		$this->assertTrue($expectedReply->Auth);
		$this->assertEquals($expectedReply->User, $authReply->User);
	}
	public function testLoginSuccessRemember() {
		$web = new Web(
			$this->userMapperObserver,
			$this->tokenMapperObserver,
			$this->cookiesObserver,
			$this->sessionObserver
		);

		$expectedReply        = new Reply();
		$expectedReply->Auth  = true;
		$expectedReply->User  = $this->userObserver;
		$expectedReply->Token = 'valid-token';

		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->will($this->returnValue($this->userObserver));
		$this->userObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue(1));
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Set')
			->withConsecutive(
				['AuthUserId', 1],
				['AuthToken', $this->isType('string')]
			);
		$this->cookiesObserver
			->expects($this->once())
			->method('Set')
			->with(
				'AuthToken',
				$this->isType('string'),
				$this->logicalAnd(
					$this->isType('integer'),
					$this->greaterThan(0)
				)
			);
		$this->tokenMapperObserver
			->expects($this->once())
			->method('SaveAuthToken');

		$authReply = $web->Login('user', 'pass', true);
		$this->assertTrue($expectedReply->Auth);
		$this->assertEquals($expectedReply->User, $authReply->User);
	}

	public function testLogout() {
		$web = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
			->setConstructorArgs([
				$this->userMapperObserver,
				$this->tokenMapperObserver,
				$this->cookiesObserver,
				$this->sessionObserver
			])
			->setMethods(['GetAuthenticatedUser', 'getValidToken'])
			->getMock();

		$authReply          = new Reply();
		$authReply->Auth    = true;
		$authReply->Token   = 'valid-token';
		$authReply->Message = 'Found valid token.';
		$authReply->User    = $this->userObserver;

		$token = new Token(1, 'series');

		$web
			->expects($this->once())
			->method('GetAuthenticatedUser')
			->will($this->returnValue($authReply));
		$web
			->expects($this->once())
			->method('getValidToken')
			->will($this->returnValue($token));

		$this->tokenMapperObserver
			->expects($this->once())
			->method('DeleteAuthToken')
			->with($token->UserID, $token->SeriesID);
		$this->cookiesObserver
			->expects($this->once())
			->method('Delete')
			->with('AuthToken');
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AuthUserId'], ['AuthToken']);

		$expectedReply = new Reply();
		$this->assertEquals(
			$expectedReply, $web->Logout($this->requestObserver)
		);
	}
	public function testLogoutNoToken() {
		$web = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
			->setConstructorArgs([
				$this->userMapperObserver,
				$this->tokenMapperObserver,
				$this->cookiesObserver,
				$this->sessionObserver
			])
			->setMethods(['GetAuthenticatedUser', 'getValidToken'])
			->getMock();

		$authReply          = new Reply();
		$authReply->Auth    = true;
		$authReply->Token   = 'valid-token';
		$authReply->Message = 'Found valid token.';
		$authReply->User    = $this->userObserver;

		$web
			->expects($this->once())
			->method('GetAuthenticatedUser')
			->will($this->returnValue($authReply));
		$web
			->expects($this->once())
			->method('getValidToken')
			->will($this->returnValue(false));

		$this->userObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue(1));

		$this->tokenMapperObserver
			->expects($this->once())
			->method('DeleteAuthToken')
			->with(1, null);
		$this->cookiesObserver
			->expects($this->once())
			->method('Delete')
			->with('AuthToken');
		$this->sessionObserver
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AuthUserId'], ['AuthToken']);

		$expectedReply = new Reply();
		$this->assertEquals(
			$expectedReply, $web->Logout($this->requestObserver)
		);
	}
}
