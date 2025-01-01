<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

// phpcs:disable
require_once __DIR__ . '/WebAuthTestSetup.php';
// phpcs:enable

class WebAuth_GetAuthenticatedUserTest extends TestCase {
	use WebAuthTestSetup;

	public function testGetAuthenticatedUserWithValidToken() {
		$claims = ['userId' => 1];

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

		// Mock the Cookies to return valid tokens
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return 'valid-token'; // Simulate valid access token
				}
				if ($key === 'RefreshToken') {
					return 'valid-refresh-token'; // Simulate valid refresh token
				}
				return $default;
			});

		$this->sessionParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // Simulate no access token in session
				}
				return $default;
			});

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		// Call the method under test
		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertTrue($reply->Auth, 'Expected Auth to be true.');
		$this->assertSame($this->userObserver, $reply->User, 'Expected the User to match.');
		$this->assertSame('valid-token', $reply->AccessToken, 'Expected the AccessToken to match.');
		$this->assertEquals('Authenticated via access token.', $reply->Message, 'Expected the Message to match.');
	}

	public function testGetAuthenticatedUserWithoutUsingSession() {
		$claims = ['userId' => 1];

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

		// Mock Cookies to return valid tokens
		$this->cookiesParamSet
			->method('Get')
			->with('AccessToken')
			->willReturn('valid-token'); // Simulate valid access token

		// Ensure Session is not called
		$this->sessionParamSet
			->expects($this->never())
			->method('Get');

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, false);

		// Call the method under test
		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertTrue($reply->Auth, 'Expected Auth to be true.');
		$this->assertSame($this->userObserver, $reply->User, 'Expected the User to match.');
		$this->assertSame('valid-token', $reply->AccessToken, 'Expected the AccessToken to match.');
		$this->assertEquals('Authenticated via access token.', $reply->Message, 'Expected the Message to match.');
	}

	public function testGetAuthenticatedUserWithValidTokenFromSession() {
		$claims = ['userId' => 1];

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

		// Mock the Session to return valid tokens
		$this->sessionParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return 'valid-token'; // Simulate valid access token
				}
				if ($key === 'RefreshToken') {
					return 'valid-refresh-token'; // Simulate valid refresh token
				}
				return $default;
			});

		$this->sessionParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // Simulate no access token in session
				}
				return $default;
			});

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, true);

		// Call the method under test
		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertTrue($reply->Auth, 'Expected Auth to be true.');
		$this->assertSame($this->userObserver, $reply->User, 'Expected the User to match.');
		$this->assertSame('valid-token', $reply->AccessToken, 'Expected the AccessToken to match.');
		$this->assertEquals('Authenticated via access token.', $reply->Message, 'Expected the Message to match.');
	}

	public function testGetAuthenticatedUserWithInvalidToken() {
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return 'invalid-token'; // Simulate invalid access token
				}
				return $default;
			});

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('invalid-token')
			->willThrowException(new InvalidTokenException('Invalid or expired token'));

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('Invalid or expired token', $reply->Message);
	}

	public function testGetAuthenticatedUserWithNoToken() {
		$this->cookiesParamSet
			->method('Get')
			->willReturn(null); // Simulate no token

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('No token provided', $reply->Message);
	}

	public function testGetAuthenticatedUserWithUserNotFound() {
		$claims = ['userId' => 1];

		// Mock the TokenManager to decode the token and return claims
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willReturn($claims);

		// Mock the UserMapper to return null (user not found)
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->willReturn(null);

		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return 'valid-token'; // Simulate valid access token
				}
				return $default;
			});

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('Invalid claims.', $reply->Message); // Updated message
	}

	public function testGetAuthenticatedUserWithTokenManagerException() {
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return 'valid-token'; // Simulate valid access token
				}
				return $default;
			});

		// Mock the TokenManager to throw an unexpected exception
		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('valid-token')
			->willThrowException(new \Exception('Unexpected error'));

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		$this->assertFalse($reply->Auth);
		$this->assertEquals('Unexpected error', $reply->Message);
	}

	public function testGetAuthenticatedUserFallbackToRefreshToken() {
		$claims    = ['userId' => 1];
		$newClaims = ['userId' => 1, 'role' => 'admin'];

		// Simulate no valid AccessToken
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // No valid access token
				}
				if ($key === 'RefreshToken') {
					return 'valid-refresh-token'; // Valid refresh token
				}
				return $default;
			});

		$this->sessionParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // No valid access token in session
				}
				if ($key === 'RefreshToken') {
					return 'valid-refresh-token'; // Valid refresh token in session
				}
				return $default;
			});

		// Mock the TokenManager to validate and decode the RefreshToken
		$this->tokenManagerObserver
			->expects($this->once())
			->method('ValidateRefreshToken')
			->with('valid-refresh-token')
			->willReturn(true);

		$this->tokenManagerObserver
			->expects($this->exactly(2))
			->method('DecodeAccessToken')
			->willReturnCallback(function ($token) use ($claims, $newClaims) {
				if ($token === 'valid-refresh-token') {
					return $claims;
				}
				if ($token === 'new-access-token') {
					return $newClaims;
				}
				throw new InvalidArgumentException('Unexpected token: ' . $token);
			});

		// Mock reissuing a new AccessToken
		$this->tokenManagerObserver
			->expects($this->once())
			->method('GenerateAccessToken')
			->with($this->userObserver)
			->willReturn('new-access-token');

		// Extend expiration of RefreshToken
		$this->tokenManagerObserver
			->expects($this->once())
			->method('ExtendRefreshTokenExpiration')
			->with('valid-refresh-token');

		// Mock the UserMapper to return a User object
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserById')
			->with(1)
			->willReturn($this->userObserver);

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertTrue($reply->Auth, 'Expected Auth to be true.');
		$this->assertSame($this->userObserver, $reply->User, 'Expected the User to match.');
		$this->assertSame('new-access-token', $reply->AccessToken, 'Expected the new AccessToken to match.');
		$this->assertSame('valid-refresh-token', $reply->RefreshToken, 'Expected the RefreshToken to match.');
		$this->assertEquals('Authenticated via refresh token.', $reply->Message, 'Expected the Message to match.');
	}

	public function testGetAuthenticatedUserWithInvalidRefreshToken() {
		// Simulate no valid AccessToken
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // No valid access token
				}
				if ($key === 'RefreshToken') {
					return 'invalid-refresh-token'; // Invalid refresh token
				}
				return $default;
			});

		$this->sessionParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // No valid access token in session
				}
				if ($key === 'RefreshToken') {
					return 'invalid-refresh-token'; // Invalid refresh token in session
				}
				return $default;
			});

		// Mock the TokenManager to invalidate the RefreshToken
		$this->tokenManagerObserver
			->expects($this->once())
			->method('ValidateRefreshToken')
			->with('invalid-refresh-token')
			->willReturn(false);

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('Invalid refresh token', $reply->Message, 'Expected the Message to indicate failure.');
	}

	public function testGetAuthenticatedUserWithInvalidRefreshTokenDecoding() {
		// Simulate no valid AccessToken
		$this->cookiesParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'AccessToken') {
					return null; // No valid access token
				}
				if ($key === 'RefreshToken') {
					return 'invalid-refresh-token'; // Invalid refresh token
				}
				return $default;
			});

		// Mock the TokenManager to validate the RefreshToken but fail on decoding
		$this->tokenManagerObserver
			->expects($this->once())
			->method('ValidateRefreshToken')
			->with('invalid-refresh-token')
			->willReturn(true);

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('invalid-refresh-token')
			->willThrowException(new InvalidTokenException('Invalid or expired refresh token'));

		// Mock the UserMapper to not be called (since decoding failed)
		$this->userMapperObserver
			->expects($this->never())
			->method('GetAuthorizedUserById');

		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);

		$reply = $auth->GetAuthenticatedUser($this->requestObserver);

		// Assert the expected behavior
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('Authentication failed.', $reply->Message, 'Expected the Message to indicate failure.');
	}
}
