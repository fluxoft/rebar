<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Http\ParameterSet;
use Fluxoft\Rebar\Http\Request;
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
	/** @var ParameterSet|MockObject */
	private $headersParamSet;
	/** @var ParameterSet|MockObject */
	private $getParamSet;

	protected function setup(): void {
		$this->userMapperObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver         = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->headersParamSet      = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
			->getMock();
		$this->getParamSet          = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
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
				if ($key === 'Headers') {
					return $this->headersParamSet;
				}
				if ($key === 'Get') {
					return $this->getParamSet;
				}
				return null;
			});
	}

	protected function teardown(): void {
		unset(
			$this->userMapperObserver,
			$this->tokenManagerObserver,
			$this->userObserver,
			$this->requestObserver,
			$this->headersParamSet
		);
	}

	public function testGetAuthenticatedUserWithValidToken() {
		$claims = ['userId' => 1];
	
		// Set the Authorization header to 'Bearer valid-token'
		$this->headersParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'Authorization') {
					return 'Bearer valid-token'; // Ensure it returns 'valid-token'
				}
				return $default;
			});
	
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
		$this->headersParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'Authorization') {
					return 'Bearer invalid-token'; // Ensure it returns 'invalid-token'
				}
				return $default;
			});
	
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
		$this->headersParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'Authorization') {
					return null; // Simulate missing header
				}
				return $default;
			});
	
		$auth = new ApiAuth($this->userMapperObserver, $this->tokenManagerObserver);
	
		$reply = $auth->GetAuthenticatedUser($this->requestObserver);
	
		$this->assertFalse($reply->Auth);
		$this->assertEquals('Missing or invalid Authorization header.', $reply->Message);
	}

	public function testLogoutWithGlobalFlag() {
		$this->getParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'globalLogout') {
					return 'true';
				}
				return $default;
			});
	
		// Mock the Authorization header for logout
		$this->headersParamSet
			->method('Get')
			->willReturnCallback(function ($key, $default = null) {
				if ($key === 'Authorization') {
					return 'Bearer valid-token';
				}
				return $default;
			});
		// 
	
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
}
