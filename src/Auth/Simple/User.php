<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Model;

/**
 * Class User
 * @package Fluxoft\Rebar\Auth\Users\Simple
 * @property int Id
 * @property string Username
 * @property string Password
 */
class User extends Model implements UserInterface {
	/**
	 * @param int $id
	 * @param $username
	 * @param $password
	 * @throws InvalidUserException
	 */
	public function __construct($id, $username, $password) {
		if (isset($id) &&
			isset($username) &&
			isset($password)
		) {
			if (!is_int($id)) {
				throw new InvalidCredentialsException('The Id must be an integer.');
			} elseif (!is_string($username) || strlen($username) === 0) {
				throw new InvalidCredentialsException('The Username must be a non-zero length string');
			} elseif (!is_string($password) || strlen($password) === 0) {
				throw new InvalidCredentialsException('The Password must be a non-zero length string');
			} else {
				parent::__construct([
					'Id' => $id,
					'Username' => $username,
					'Password' => $password
				]);
			}
		} else {
			throw new InvalidCredentialsException('User must be initialized with ID, Username, and Password properties');
		}
	}

	/**
	 * @return mixed
	 */
	public function GetID(): mixed {
		return $this->properties['Id'];
	}

	/**
	 * @return string
	 */
	public function GetUsername() {
		return $this->properties['Username'];
	}

	/**
	 * @return string
	 */
	public function GetPassword() {
		return '********';
	}

	/**
	 * Check the given password to see if it matches the User's Password.
	 * @param $password
	 * @return bool
	 */
	public function IsPasswordValid($password) {
		return $this->properties['Password'] === $password;
	}
}
