<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use PHPUnit\Framework\TestCase;
use TypeError;

class UserTest extends TestCase {
	/**
	 * @param $id
	 * @param $username
	 * @param $password
	 * @dataProvider constructorProvider
	 */
	public function testUser($id, $username, $password) {
		$isValidUser = is_int($id)
			&& is_string($username) && strlen($username) > 0
			&& is_string($password) && strlen($password) > 0;

		if (!is_int($id)) {
			$this->expectException(TypeError::class);
		} elseif (is_null($username) || strlen($username) < 1) {
			$this->expectException(InvalidCredentialsException::class);
		} elseif (strlen($password) < 1) {
			$this->expectException(InvalidCredentialsException::class);
		} elseif (!$isValidUser) {
			$this->expectException(InvalidCredentialsException::class);
		}

		$user = new User($id, $username, $password);

		if ($isValidUser) {
			$this->assertEquals($id, $user->GetId());
			$this->assertEquals($username, $user->Username);
			$this->assertEquals('********', $user->Password);
			$this->assertTrue($user->IsPasswordValid($password));
		}
	}
	public function constructorProvider() {
		return [
			// Invalid cases
			[null, null, null],          // Invalid: All null
			['foo', 'foo', 'foo'],       // Invalid: ID is string
			[0, false, 'foo'],           // Invalid: Username is not a string
			[0, '', 'foo'],              // Invalid: Username is empty
			[0, 'foo', false],           // Invalid: Password is not a string
			[0, 'foo', ''],              // Invalid: Password is empty
			// Valid case
			[1, 'foo', 'bar'],           // Valid
		];
	}

	public function testHashPassword() {
		$user = new User(1, 'foo', 'bar');
		$hash = password_hash('bar', PASSWORD_DEFAULT);
		$this->assertTrue(password_verify('bar', $hash));
		$this->assertTrue($user->IsPasswordValid('bar'));
	}

	public function testGetAuthUsernameProperty() {
		$user = new User(1, 'foo', 'bar');
		$this->assertEquals('Username', $user->GetAuthUsernameProperty());
	}
}
