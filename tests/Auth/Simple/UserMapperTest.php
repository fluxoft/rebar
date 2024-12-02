<?php

namespace Fluxoft\Rebar\Auth\Simple;

use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase {
	/**
	 * @param array $usersToSet
	 * @dataProvider userMapperProvider
	 */
	public function testUserMapper(array $usersToSet) {
		// Create the User objects
		$users = [];
		foreach ($usersToSet as $user) {
			$users[] = new User($user['id'], $user['username'], $user['password']);
		}
		$userMapper = new UserMapper($users);

		foreach ($usersToSet as $userArray) {
			// Retrieve the User by username and password from the UserMapper
			$authUser = $userMapper->GetAuthorizedUserForUsernameAndPassword(
				$userArray['username'],
				$userArray['password']
			);

			// Find the corresponding original User object
			$expectedUser = array_values(array_filter($users, function (User $user) use ($userArray) {
				return $user->Username === $userArray['username'];
			}))[0]; // There should only be one match

			$this->assertEquals($expectedUser, $authUser);

			// Retrieve the User by ID from the UserMapper
			$authUser = $userMapper->GetAuthorizedUserById($userArray['id']);

			// Find the corresponding original User object by ID
			$expectedUser = array_values(array_filter($users, function (User $user) use ($userArray) {
				return $user->GetId() === $userArray['id'];
			}))[0]; // There should only be one match

			$this->assertEquals($expectedUser, $authUser);
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
					],
					[
						'id' => 2,
						'username' => 'baz',
						'password' => 'qux'
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

		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException');
		$this->expectExceptionMessage('Invalid username or password.');

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
