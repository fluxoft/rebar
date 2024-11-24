<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Data\Db\Exceptions\ModelException;
use Fluxoft\Rebar\Model;

/**
 * Class User
 * @package Fluxoft\Rebar\Auth\Db
 */
abstract class User extends Model implements UserInterface {
	protected $authUsernameProperty = 'Email';
	protected $authPasswordProperty = 'Password';

	public function __construct(array $properties = []) {
		// Call the parent constructor to ensure $properties is initialized from the defaults
		parent::__construct($properties);

		// Ensure the username and password properties are defined
		if (!array_key_exists($this->authUsernameProperty, $this->properties)) {
			throw new ModelException(sprintf(
				'The username property %s must be defined in the properties array.',
				$this->authUsernameProperty
			));
		}
		if (!array_key_exists($this->authPasswordProperty, $this->properties)) {
			throw new ModelException(sprintf(
				'The password property %s must be defined in the properties array.',
				$this->authPasswordProperty
			));
		}
	}

	public function GetAuthUsernameProperty() {
		return $this->authUsernameProperty;
	}

	/**
	 * Regenerating the hash with an available hash as the options parameter should
	 * produce the same hash if the same password is passed.
	 * @param $password
	 * @return bool
	 */
	public function IsPasswordValid($password) {
		$hash = $this->properties[$this->authPasswordProperty];
		return crypt($password, $hash) === $hash;
	}

	/*
	 * Special get/set handlers for the password property.
	 */
	public function __get($key) {
		if ($key === $this->authPasswordProperty) {
			return '********';
		}
		return parent::__get($key);
	}
	public function __set($key, $value) {
		if ($key === $this->authPasswordProperty) {
			$value = $this->generateHash($value);
		}
		parent::__set($key, $value);
	}

	protected function generateHash ($password, $cost = 11) {
		/* To generate the salt, first generate enough random bytes. Because
		 * base64 returns one character for each 6 bits, the we should generate
		* at least 22*6/8=16.5 bytes, so we generate 17. Then we get the first
		* 22 base64 characters
		*/
		$salt = substr(base64_encode(openssl_random_pseudo_bytes(17)), 0, 22);
		/*
		 * As blowfish takes a salt with the alphabet ./A-Za-z0-9 we have to
		 * replace any '+' in the base64 string with '.'. We don't have to do
		 * anything about the '=', as this only occurs when the b64 string is
		 * padded, which is always after the first 22 characters.
		 */
		$salt = str_replace("+", ".", $salt);
		/* Next, create a string that will be passed to crypt, containing all
		 * of the settings, separated by dollar signs
		*/
		$param = '$' . implode('$', [
				"2y", //select the most secure version of blowfish (>=PHP 5.3.7)
				str_pad($cost, 2, "0", STR_PAD_LEFT), //add the cost in two digits
				$salt                                 //add the salt
			]);

		//now do the actual hashing
		return crypt($password, $param);
	}
}
