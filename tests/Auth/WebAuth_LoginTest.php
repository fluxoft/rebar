<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// phpcs:disable
require_once __DIR__ . '/WebAuthTestSetup.php';
// phpcs:enable

class WebAuth_LoginTest extends TestCase {
	use WebAuthTestSetup;

	public function testLoginWithValidCredentials() {
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with('validUser', 'validPassword')
			->willReturn($this->userObserver);

		$this->tokenManagerObserver
			->expects($this->once())
			->method('GenerateAccessToken')
			->with($this->userObserver)
			->willReturn('access-token');

		$this->tokenManagerObserver
			->expects($this->once())
			->method('GenerateRefreshToken')
			->with($this->userObserver)
			->willReturn('refresh-token');

		$this->tokenManagerObserver
			->expects($this->once())
			->method('DecodeAccessToken')
			->with('access-token')
			->willReturn(['userId' => 1]);

		$auth  = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver, true);
		$reply = $auth->Login($this->requestObserver, 'validUser', 'validPassword', true);

		$this->assertTrue($reply->Auth);
		$this->assertSame($this->userObserver, $reply->User);
		$this->assertSame('access-token', $reply->AccessToken);
		$this->assertSame('refresh-token', $reply->RefreshToken);
		$this->assertEquals('Authenticated via username and password.', $reply->Message);
	}

	public function testLoginThrowsExceptionForInvalidCredentials() {
		$this->userMapperObserver
			->expects($this->once())
			->method('GetAuthorizedUserForUsernameAndPassword')
			->with('invalidUser', 'invalidPassword')
			->willThrowException(new InvalidCredentialsException('Invalid username or password.'));
	
		$auth = new WebAuth($this->userMapperObserver, $this->tokenManagerObserver);
	
		$this->expectException(InvalidCredentialsException::class);
		$this->expectExceptionMessage('Invalid username or password.');
	
		$auth->Login($this->requestObserver, 'invalidUser', 'invalidPassword');
	}
}
