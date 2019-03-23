<?php

namespace Fluxoft\Rebar\Auth\Simple;

use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase {
	/**
	 * @param array $usersToSet
	 * @dataProvider userMapperProvider
	 */
	public function testUserMapper(array $usersToSet) {
		$users = [];
		foreach ($usersToSet as $user) {
			$users[] = new User($user['id'], $user['username'], $user['password']);
		}
		$userMapper = new UserMapper($users);

		foreach ($usersToSet as $userArray) {
			$authUser   = $userMapper->GetAuthorizedUserForUsernameAndPassword(
				$user['username'],
				$user['password']
			);
			$expectUser = new User($user['id'], $user['username'], $user['password']);
			$this->assertEquals($expectUser, $authUser);

			$authUser = $userMapper->GetAuthorizedUserById($expectUser->Id);
			$this->assertEquals($expectUser, $authUser);
		}
	}
	public function userMapperProvider() {
		return [
			[
				'usersToSet' => [
					[
						'id' => 1,
						'username' => 'foo',
						'password' => 'bar'
					]
				]
			]
		];
	}

	public function testAddExistingUser() {
		$user = new User(1, 'foo', 'bar');

		$userMapper = new UserMapper([$user]);

		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserMapperException');
		$this->expectExceptionMessage(sprintf(
			'A user with the ID "%s" or Username "%s" already exists and cannot be added.',
			$user->ID,
			$user->Username
		));

		$userMapper->AddUser($user);
	}

	public function testAuthorizedUserForUsernameAndPasswordUsernameNotFound() {
		$user = new User(1, 'foo', 'bar');

		$userMapper = new UserMapper([$user]);

		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException');
		$this->expectExceptionMessage(sprintf(
			'No user was found with username "%s"',
			'notfound'
		));

		$userMapper->GetAuthorizedUserForUsernameAndPassword('notfound', 'password');
	}

	public function testAuthorizedUserForUsernameAndPasswordInvalidPassword() {
		$user = new User(1, 'foo', 'bar');

		$userMapper = new UserMapper([$user]);

		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException');
		$this->expectExceptionMessage('The password is incorrect.');

		$userMapper->GetAuthorizedUserForUsernameAndPassword('foo', 'wrong');
	}

	public function testAuthorizedUserByIdNotFound() {
		$user = new User(1, 'foo', 'bar');

		$userMapper = new UserMapper([$user]);

		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException');
		$this->expectExceptionMessage(sprintf(
			'No user was found with ID "%s"',
			2
		));

		$userMapper->GetAuthorizedUserById(2);
	}
}
