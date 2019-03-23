<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserMapperException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserMapperInterface;

class UserMapper implements UserMapperInterface {
	/** @var User[] $users */
	protected $users = [];

	/**
	 * @param User[] $users
	 */
	public function __construct(array $users = []) {
		foreach ($users as $user) {
			$this->AddUser($user);
		}
	}

	/**
	 * @param User $user
	 * @throws UserMapperException
	 */
	public function AddUser(User $user) {
		// Do not allow adding User with the same ID or Username as an existing one.
		$users = array_filter($this->users, function (User $existingUser) use ($user) {
			return ($user->ID === $existingUser->Id || $user->Username === $existingUser->Username);
		}) ;
		if (!empty($users)) {
			throw new UserMapperException(sprintf(
				'A user with the ID "%s" or Username "%s" already exists and cannot be added.',
				$user->ID,
				$user->Username
			));
		}
		$this->users[] = $user;
	}

	/**
	 * Return the user for the given username and password.
	 * @param $username
	 * @param $password
	 * @return mixed
	 * @throws InvalidPasswordException
	 * @throws UserNotFoundException
	 */
	public function GetAuthorizedUserForUsernameAndPassword($username, $password) {
		$users = array_filter($this->users, function (User $user) use ($username) {
			return $user->Username === $username;
		});
		if (empty($users)) {
			throw new UserNotFoundException(sprintf(
				'No user was found with username "%s"',
				$username
			));
		} else {
			/** @var User $user */
			$user = $users[0];
			if (!$user->IsPasswordValid($password)) {
				throw new InvalidPasswordException('The password is incorrect.');
			}
			return $user;
		}
	}

	/**
	 * Return the user for the given ID
	 * @param $id
	 * @return mixed
	 * @throws UserNotFoundException
	 */
	public function GetAuthorizedUserById($id) {
		$users = array_filter($this->users, function (User $user) use ($id) {
			return $user->GetID() === $id;
		});
		if (empty($users)) {
			throw new UserNotFoundException(sprintf(
				'No user was found with ID "%s"',
				$id
			));
		} else {
			return $users[0];
		}
	}
}
