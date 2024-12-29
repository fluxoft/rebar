<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiAuthTest extends TestCase {
	/** @var UserMapperInterface|MockObject */
	private $userMapperObserver;
	/** @var TokenManager|MockObject */
	private $tokenManagerObserver;
	/** @var UserInterface|MockObject */
	private $userObserver;
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var Cookies|MockObject */
	private $cookiesParamSet;

	protected function setup(): void {
		$this->userMapperObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver         = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->cookiesParamSet      = $this->getMockBuilder('\Fluxoft\Rebar\Http\Cookies')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenManagerObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\TokenManager')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver      = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();

		// Mock the Request::__get method to return the Headers parameter set
		$this->requestObserver
			->method('__get')
			->willReturnCallback(function ($key) {
				if ($key === 'Cookies') {
					return $this->cookiesParamSet;
				}
				return null;
			});
	}

	protected function teardown(): void {
		unset(
			$this->userMapperObserver,
			$this->tokenManagerObserver,
			$this->userObserver,
			$this->requestObserver
		);
	}

	public function testGetAuthenticatedUserWithValidToken() {
		$claims = ['userId' => 1];

		// Set the Authorization header to 'Bearer valid-token'
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer valid-token');

		// Mock the TokenManager to decode the token and return claims
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn($claims);

		// Mock the UserMapper to return a User object
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->willReturn($this->userObserver);

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		// Call the method under test
		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertTrue($reply->Auth, 'Expected Auth to be true.');
		$this->assertSame($this->userObserver, $reply->User, 'Expected the User to match.');
		$this->assertSame('valid-token', $reply->AccessToken, 'Expected the AccessToken to match.');
		$this->assertEquals('Authenticated via access token.', $reply->Message, 'Expected the Message to match.');
	}

	public function testGetAuthenticatedUserWithInvalidToken() {
		// Override Authorization header mock for invalid token
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer invalid-token');

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('invalid-token')
			->willThrowException(new InvalidTokenException('Invalid or expired token'));

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('Invalid or expired token', $reply->Message);
	}

	public function testGetAuthenticatedUserWithMissingHeader() {
		// Mock empty Authorization header
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn(null);

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('Missing or invalid Authorization header.', $reply->Message);
	}

	public function testLogoutWithGlobalFlag() {
		// Mock the globalLogout parameter
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn('true');

		// Mock the Authorization header for logout
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer valid-token');

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn(['userId' => 1]);

		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshTokensByUserId')
			->with(1);

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		$this->assertTrue($reply->Auth);
		$this->assertEquals('Logged out globally.', $reply->Message);
	}

	public function testGetAuthenticatedUserWithMissingUserIdInClaims() {
		// Set the Authorization header to a valid token
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer valid-token');

		// Mock the TokenManager to decode the token without a userId
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn([]); // No userId in claims

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Invalid token payload.', $reply->Message);
	}

	public function testGetAuthenticatedUserWithUserNotFound() {
		// Set the Authorization header to a valid token
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer valid-token');

		// Mock the TokenManager to decode the token and return valid claims
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn(['userId' => 1]);

		// Mock the UserMapper to return null, simulating a user not found
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->willReturn(null);

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('User not found.', $reply->Message);
	}

	public function testLogoutWithMissingAuthorizationHeader() {
		// Mock the globalLogout parameter
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn('true');

		// Mock an empty Authorization header
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn(null);

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Missing or invalid Authorization header.', $reply->Message);
	}

	public function testLogoutWithInvalidToken() {
		// Mock the globalLogout parameter
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn('true');

		// Mock the Authorization header with an invalid token
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer invalid-token');

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('invalid-token')
			->willThrowException(new InvalidTokenException('Token is invalid or expired.'));

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Token is invalid or expired.', $reply->Message);
	}

	public function testLogoutWithoutGlobalFlagAndWithMissingRefreshToken() {
		// Simulate the absence of the globalLogout parameter
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn(null);

		// Simulate missing RefreshToken in both Headers and Cookies
		$this->requestObserver
			->method('Headers')
			->with('RefreshToken')
			->willReturn(null);
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'RefreshToken') {
					return null;
				}
				return $default;
			});

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Missing RefreshToken header.', $reply->Message);
	}

	public function testLogoutWithoutGlobalFlagAndWithRefreshTokenInCookies() {
		// Simulate the absence of the globalLogout parameter
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn(null);

		// Simulate RefreshToken in Cookies
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'RefreshToken') {
					return 'cookie-refresh-token'; // Token in cookies
				}
				return $default;
			});
		$this->cookiesParamSet
			->expects($this->once())
			->method('Delete')
			->with('RefreshToken'); // Ensure the cookie is deleted

		// Simulate no RefreshToken in Headers
		$this->requestObserver
			->method('Headers')
			->with('RefreshToken')
			->willReturn(null);

		// Expect the RefreshToken to be revoked
		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshToken')
			->with('cookie-refresh-token');

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected success response
		$this->assertFalse($reply->Auth); // Logout sets Auth to false
		$this->assertEquals('Logged out.', $reply->Message);
	}

	public function testLogoutWithoutGlobalFlagAndWithRefreshTokenInHeaders() {
		// Simulate the globalLogout flag being false
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn(null);

		// Mock the Headers to return a valid RefreshToken
		$this->requestObserver
			->method('Headers')
			->with('RefreshToken')
			->willReturn('valid-refresh-token');

		// Expect the TokenManager to revoke the refresh token
		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshToken')
			->with('valid-refresh-token');

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected success response
		$this->assertFalse($reply->Auth); // Logout sets Auth to false
		$this->assertEquals('Logged out.', $reply->Message);
	}

	public function testLogoutWithInvalidAuthorizationHeader() {
		// Simulate a global logout attempt
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn('true');

		// Mock the Authorization header to an invalid value
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('InvalidHeader');

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Missing or invalid Authorization header.', $reply->Message);
	}

	public function testLogoutWithInvalidRefreshToken() {
		// Simulate a single-session logout (no global flag)
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn(null);

		// Mock the RefreshToken header in the request
		$this->requestObserver
			->method('Headers')
			->with('RefreshToken')
			->willReturn('invalid-refresh-token');

		// Mock the behavior of the tokenManager to throw an exception when revoking
		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshToken')
			->with('invalid-refresh-token')
			->willThrowException(new \Exception('Token revocation failed'));

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Failed to log out: Token revocation failed', $reply->Message);
	}

	public function testLogoutWithNullUserIdInClaims() {
		// Simulate a global logout attempt
		$this->requestObserver
			->method('Get')
			->with('globalLogout')
			->willReturn('true');

		// Set the Authorization header to a valid token
		$this->requestObserver
			->method('Headers')
			->with('Authorization')
			->willReturn('Bearer valid-token');

		// Mock the TokenManager to decode the token with a null userId
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn(['userId' => null]); // Explicit null for userId

		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->Logout($this->requestObserver);

		// Assert the expected error response
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Invalid token payload.', $reply->Message);
	}

	public function testHandleAuthFailure(): void {
		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);

		/** @var Response|MockObject $mockResponse */
		$mockResponse = $this->createMock(Response::class);

		$mockResponse
			->expects($this->once())
			->method('Halt')
			->with(403, 'Access denied');

		$auth->HandleAuthFailure($this->requestObserver, $mockResponse);
	}
}
