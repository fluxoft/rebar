<?php

namespace Fluxoft\Rebar\Auth\Db;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
	public function testUnsetUsername() {
		$authUsernameProperty = 'Email';
		$authPasswordProperty = 'Password';
		$properties           = [
			'Id' => '',
			'Email' => '',
			'Password' => ''
		];
		$propertyDbMap        = [
			'Id' => 'id',
			'Password' => 'password'
		];
		$dataRow              = [
			'Id' => 1,
			'Email' => 'joe@fluxoft.com',
			'Password' => 'password'
		];

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\ModelException');
		$this->expectExceptionMessage(sprintf(
			'The username property %s must be defined in the propertyDbMap.',
			$authUsernameProperty
		));

		$user = new ConcreteUser(
			$dataRow,
			$properties,
			$propertyDbMap,
			$authUsernameProperty,
			$authPasswordProperty
		);
		unset($user);
	}

	public function testUnsetPassword() {
		$authUsernameProperty = 'Email';
		$authPasswordProperty = 'Password';
		$properties           = [
			'Id' => '',
			'Email' => '',
			'Password' => ''
		];
		$propertyDbMap        = [
			'Id' => 'id',
			'Email' => 'email'
		];
		$dataRow              = [
			'Id' => 1,
			'Email' => 'joe@fluxoft.com',
			'Password' => 'password'
		];

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\ModelException');
		$this->expectExceptionMessage(sprintf(
			'The password property %s must be defined in the propertyDbMap.',
			$authPasswordProperty
		));

		$user = new ConcreteUser(
			$dataRow,
			$properties,
			$propertyDbMap,
			$authUsernameProperty,
			$authPasswordProperty
		);
		unset($user);
	}

	public function testPropertyAccessors() {
		$user = new ConcreteUser([
			'Id' => 1,
			'UserEmail' => 'joe@fluxoft.com',
			'PassPhrase' => 'password'
		]);

		/*
		 * This is used by the UserMapper in case someone has set
		 * a different property name as their username property.
		 */
		$this->assertEquals('UserEmail', $user->GetAuthUsernameProperty());

		$this->assertEquals(1, $user->Id);
		$this->assertEquals('joe@fluxoft.com', $user->UserEmail);

		/*
		 * Do not allow retrieving the actual password.
		 */
		$this->assertEquals('********', $user->PassPhrase);

		// Set a password and confirm it was hashed correctly.
		$newPassword      = 'VerySecurePassword';
		$user->PassPhrase = $newPassword;

		// test IsValid function for new password
		$this->assertTrue($user->IsPasswordValid($newPassword));
	}
}

// @codingStandardsIgnoreStart
class ConcreteUser extends User {
	// @codingStandardsIgnoreEnd
	protected $authUsernameProperty = 'UserEmail';
	protected $authPasswordProperty = 'PassPhrase';

	public function __construct(
		array $dataRow = [],
		array $properties    = null,
		array $propertyDbMap = null,
		string $authUsernameProperty = null,
		string $authPasswordProperty = null
	) {
		if (isset($properties) && isset($propertyDbMap)) {
			$this->properties    = $properties;
			$this->propertyDbMap = $propertyDbMap;
		}
		if (isset($authUsernameProperty)) {
			$this->authUsernameProperty = $authUsernameProperty;
		}
		if (isset($authPasswordProperty)) {
			$this->authPasswordProperty = $authPasswordProperty;
		}
		parent::__construct($dataRow);
	}

	public function PublicGenerateHash($password, $cost = 11) {
		return $this->generateHash($password, $cost);
	}
	public function GetRealPassword() {
		return $this->properties[$this->authPasswordProperty];
	}

	protected $properties    = [
		'Id' => 0,
		'UserEmail' => '',
		'PassPhrase' => ''
	];
	protected $propertyDbMap = [
		'Id' => 'id',
		'UserEmail' => 'email',
		'PassPhrase' => 'password'
	];
	protected $dbTable       = 'users';
}
