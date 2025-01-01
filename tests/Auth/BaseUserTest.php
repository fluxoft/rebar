<?php

namespace Fluxoft\Rebar\Auth;

use PHPUnit\Framework\TestCase;

class BaseUserTest extends TestCase {
	public function testGetIdReturnsCorrectValue() {
		$user = new BaseUser(['Id' => 42]);
		$this->assertEquals(42, $user->GetId());
	}

	public function testGetAuthUsernamePropertyReturnsCorrectPropertyName() {
		$user = new BaseUser();
		$this->assertEquals('Username', $user->GetAuthUsernameProperty());
	}

	public function testIsPasswordValidReturnsTrueForMatchingPassword() {
		$user           = new BaseUser();
		$user->Password = 'correct_password'; // Invoke SetPassword
		$this->assertTrue($user->IsPasswordValid('correct_password'));
	}

	public function testIsPasswordValidReturnsFalseForNonMatchingPassword() {
		$user           = new BaseUser();
		$user->Password = 'correct_password'; // Invoke SetPassword
		$this->assertFalse($user->IsPasswordValid('wrong_password'));
	}

	public function testSetPasswordHashesPasswordCorrectly() {
		$user           = new BaseUser();
		$user->Password = 'password123'; // Invoke SetPassword

		// Verify that the password is hashed and not stored in plaintext
		$this->assertNotEquals('password123', $user->properties['Password']);
		$this->assertTrue(password_verify('password123', $user->properties['Password']));
	}

	public function testGetPasswordReturnsMaskedPassword() {
		$user           = new BaseUser();
		$user->Password = 'password123'; // Invoke SetPassword
		$this->assertEquals('********', $user->Password); // Invoke getPassword
	}
}
