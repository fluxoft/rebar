<?php

namespace Fluxoft\Rebar\Auth\Simple;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Model;

/**
 * Class User
 * @package Fluxoft\Rebar\Auth\Users\Simple
 * @property int    $Id
 * @property string $Username
 * @property string $Password
 */
class User extends Model implements UserInterface {
	protected static array $defaultProperties = [
		'Id'	   => 0,
		'Username' => '',
		'Password' => ''
	];
	public function __construct(int $id, string $username, string $password) {
		parent::__construct([
			'Id'	   => $id,
			'Username' => $username
		]);
		$this->Password = $password;

		if (!$this->IsValid()) {
			throw new InvalidCredentialsException('Invalid User properties: '.implode(', ', $this->GetValidationErrors()));
		}
	}

	// UserInterface implementation
	public function GetId(): mixed {
		return $this->properties['Id'];
	}
	public function GetAuthUsernameProperty(): string {
		return 'Username';
	}
	public function IsPasswordValid($password): bool {
		return password_verify($password, $this->properties['Password']);
	}

	protected function validateUsername(string $value): bool|string {
		if (strlen($value) < 1) {
			return 'Username must be at least 1 character long.';
		}
		return true;
	}

	// password getter/setter (stored as a hash, but returned as a string with asterisks for obfuscation)
	protected function getPassword(): string {
		return '********';
	}
	protected function setPassword(string $password): void {
		if (strlen($password) < 1) {
			throw new InvalidCredentialsException('Password must be at least 1 character long.');
		}
		$this->properties['Password'] = $this->hashPassword($password);
	}
	protected function hashPassword(string $password): string {
		return password_hash($password, PASSWORD_DEFAULT);
	}
}
