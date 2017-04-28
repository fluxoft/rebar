<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Simple\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
	/**
	 * @param $id
	 * @param $username
	 * @param $password
	 * @dataProvider constructorProvider
	 */
	public function testUser($id, $username, $password) {
		$validUser = false;
		if (isset($id) &&
			isset($username) &&
			isset($password)
		) {
			if (!is_int($id)) {
				$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidUserException');
				$this->expectExceptionMessage('The Id must be an integer.');
			} elseif (!is_string($username) || strlen($username) === 0) {
				$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidUserException');
				$this->expectExceptionMessage('The Username must be a non-zero length string');
			} elseif (!is_string($password) || strlen($password) === 0) {
				$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidUserException');
				$this->expectExceptionMessage('The Password must be a non-zero length string');
			} else {
				$validUser = true;
			}
		} else {
			$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidUserException');
			$this->expectExceptionMessage('User must be initialized with ID, Username, and Password properties');
		}

		$user = new User($id, $username, $password);

		if ($validUser) {
			$this->assertEquals($id, $user->Id);
			$this->assertEquals($username, $user->Username);
			$this->assertEquals('********', $user->Password);
			$this->assertTrue($user->IsPasswordValid($password));
		}
	}
	public function constructorProvider() {
		return [
			[
				'id' => null,
				'username' => null,
				'password' => null
			],
			[
				'id' => 'foo',
				'username' => 'foo',
				'password' => 'foo',
			],
			[
				'id' => 0,
				'username' => false,
				'password' => 'foo'
			],
			[
				'id' => 0,
				'username' => '',
				'password' => 'foo'
			],
			[
				'id' => 0,
				'username' => 'foo',
				'password' => false
			],
			[
				'id' => 0,
				'username' => 'foo',
				'password' => ''
			],
			[
				'id' => 0,
				'username' => 'foo',
				'password' => 'bar'
			]
		];
	}
}
