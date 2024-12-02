<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Auth\Exceptions\UserMapperException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Auth\UserMapperInterface;

class UserMapper implements UserMapperInterface {
	/** @var array<User> */
	protected array $users = [];

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
		$users = array_values(array_filter($this->users, function (User $existingUser) use ($user) {
			return ($user->GetId() === $existingUser->GetId() || $user->Username === $existingUser->Username);
		}));
		if (!empty($users)) {
			throw new UserMapperException(sprintf(
				'A user with the ID "%s" or Username "%s" already exists and cannot be added.',
				$user->GetId(),
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
	public function GetAuthorizedUserForUsernameAndPassword(string $username, string $password): UserInterface {
		$users = array_filter($this->users, function (User $user) use ($username) {
			return $user->Username === $username;
		});
		if (empty($users)) {
			throw new UserNotFoundException(sprintf(
				'No user was found with username "%s"',
				$username
			));
		} else {
			// reset the array keys
			$users = array_values($users);
			/** @var User $user */
			$user = $users[0];
			if (!$user->IsPasswordValid($password)) {
				throw new InvalidCredentialsException('Invalid username or password.');
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
	public function GetAuthorizedUserById(mixed $id): UserInterface {
		$users = array_values(array_filter($this->users, function (User $user) use ($id) {
			return $user->GetId() === $id;
		}));
		if (empty($users)) {
			throw new UserNotFoundException(sprintf(
				'No user was found with ID "%s"',
				$id
			));
		} else {
			// reset the array keys
			$users = array_values($users);
			return $users[0];
		}
	}

	// methods for saving files and loading them from the filesystem, skip tests on these
	// @codeCoverageIgnoreStart
	public function SaveToFile(string $filePath): void {
		if (file_put_contents($filePath, serialize($this->users)) === false) {
			throw new \RuntimeException(sprintf('Failed to save users to file: %s', $filePath));
		}
	}
	
	public function LoadFromFile(string $filePath): void {
		if (!file_exists($filePath)) {
			throw new \RuntimeException(sprintf('File not found: %s', $filePath));
		}
		// @codingStandardsIgnoreLine
		$users = @unserialize(file_get_contents($filePath));
		if ($users === false) {
			throw new \RuntimeException(sprintf('Failed to load users from file: %s', $filePath));
		}
		$this->users = $users;
	}	
	// @codeCoverageIgnoreEnd
}
