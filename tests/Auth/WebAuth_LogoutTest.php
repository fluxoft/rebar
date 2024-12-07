<?php

namespace Fluxoft\Rebar\Auth;

// phpcs:disable
require_once __DIR__ . '/WebAuthTestSetup.php';
// phpcs:enable

class WebAuth_LogoutTest extends \PHPUnit\Framework\TestCase {
	use WebAuthTestSetup;

	public function testLogoutWithoutTokens() {
		$deletedCookies = [];
		$this->cookiesParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedCookies) {
				$deletedCookies[] = $key;
			});
	
		$deletedSession = [];
		$this->sessionParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedSession) {
				$deletedSession[] = $key;
			});
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshToken');
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshTokensByUserId');
	
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, true);
	
		$reply = $auth->Logout($this->requestObserver);
	
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('User logged out from this session.', $reply->Message, 'Expected the default logout message.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedCookies, 'Expected cookies to be deleted.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedSession, 'Expected session tokens to be deleted.');
	}

	public function testLogoutWithoutUsingSession() {
		// Mock the session to ensure it's not accessed
		$this->sessionParamSet
			->expects($this->never())
			->method('Delete');
	
		// Mock the cookies to simulate token deletion
		$this->cookiesParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->withConsecutive(['AccessToken'], ['RefreshToken']);
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshToken');
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshTokensByUserId');
	
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, false);
	
		$reply = $auth->Logout($this->requestObserver);
	
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('User logged out from this session.', $reply->Message, 'Expected the default logout message.');
	}

	public function testLogoutWithRefreshToken() {
		$deletedCookies = [];
		$this->cookiesParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedCookies) {
				$deletedCookies[] = $key;
			});
	
		$deletedSession = [];
		$this->sessionParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedSession) {
				$deletedSession[] = $key;
			});
	
		$this->cookiesParamSet
			->method('Get')
			->with('RefreshToken')
			->willReturn('valid-refresh-token');
	
		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshToken')
			->with('valid-refresh-token');
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshTokensByUserId');
	
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, true);
	
		$reply = $auth->Logout($this->requestObserver);
	
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('User logged out from this session.', $reply->Message, 'Expected the logout message.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedCookies, 'Expected cookies to be deleted.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedSession, 'Expected session tokens to be deleted.');
	}

	public function testGlobalLogout() {
		$deletedCookies = [];
		$this->cookiesParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedCookies) {
				$deletedCookies[] = $key;
			});
	
		$deletedSession = [];
		$this->sessionParamSet
			->expects($this->exactly(2))
			->method('Delete')
			->willReturnCallback(function ($key) use (&$deletedSession) {
				$deletedSession[] = $key;
			});
	
		$this->cookiesParamSet
			->method('Get')
			->with('RefreshToken')
			->willReturn('valid-refresh-token');
	
		$this->getParamSet
			->method('Get')
			->with('globalLogout', false)
			->willReturn(true);
	
		$this->tokenManagerObserver
			->expects($this->once())
			->method('RevokeRefreshTokensByUserId')
			->with(1);
	
		$this->tokenManagerObserver
			->expects($this->never())
			->method('RevokeRefreshToken');
	
		$this->userMapperObserver
			->expects($this->never())
			->method('GetAuthorizedUserById');
	
		$this->tokenManagerObserver
			->method('DecodeAccessToken')
			->willReturn(['userId' => 1]);
	
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, true);
	
		$reply = $auth->Logout($this->requestObserver);
	
		$this->assertFalse($reply->Auth, 'Expected Auth to be false.');
		$this->assertEquals('User logged out from all devices.', $reply->Message, 'Expected the global logout message.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedCookies, 'Expected cookies to be deleted.');
		$this->assertEquals(['AccessToken', 'RefreshToken'], $deletedSession, 'Expected session tokens to be deleted.');
	}
}
