<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Model;

/**
 * Class BaseUser
 * Provides a default implementation of the UserInterface.
 * Extend this class to create your own user models.
 *
 * @package Fluxoft\Rebar\Auth
 */
class BaseUser extends Model implements UserInterface {
	/**
	 * Define user properties.
	 * Extend this in subclasses to add additional user properties.
	 */
	protected static array $defaultProperties = [
		'Id'       => null,
		'Username' => null,
		'Password' => null,
	];
	protected string $authUserIdProperty      = 'Id';
	protected string $authUsernameProperty    = 'Username';
	protected string $authPasswordProperty    = 'Password';

	/**
	 * @inheritDoc
	 */
	public function GetId(): mixed {
		return $this->properties[$this->authUserIdProperty];
	}

	/**
	 * @inheritDoc
	 */
	public function GetAuthUsernameProperty(): string {
		return $this->authUsernameProperty;
	}

	/**
	 * @inheritDoc
	 */
	public function IsPasswordValid($password): bool {
		return password_verify($password, $this->properties[$this->authPasswordProperty]);
	}

	/**
	 * When getting the password, return a masked version.
	 */
	protected function getPassword(): string {
		return '********';
	}
	/**
	 * Set a hashed password for the user.
	 *
	 * @param string $password Plaintext password
	 * @return void
	 */
	protected function setPassword(string $password): void {
		$this->properties[$this->authPasswordProperty] = password_hash($password, PASSWORD_DEFAULT);
	}
}
