<?php

namespace Fluxoft\Rebar\Auth;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultClaimsProviderTest extends TestCase {
	public function testGetClaims() {
		// Create a mock UserInterface instance
		$userMock = $this->createMock(UserInterface::class);

		// Mock the GetId method to return a specific value
		/** @var UserInterface|MockObject $userMock */
		$userMock->expects($this->once())
			->method('GetId')
			->willReturn(123);

		// Instantiate the DefaultClaimsProvider
		$claimsProvider = new DefaultClaimsProvider();

		// Call GetClaims with the mocked user
		$claims = $claimsProvider->GetClaims($userMock);

		// Assert the returned claims array is as expected
		$this->assertIsArray($claims, 'Expected claims to be an array.');
		$this->assertArrayHasKey('userId', $claims, 'Expected claims array to have a "userId" key.');
		$this->assertEquals(123, $claims['userId'], 'Expected "userId" in claims to match the user ID.');
	}
}
