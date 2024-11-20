<?php

namespace Fluxoft\Rebar\Auth\Db;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
	public function testUnsetUsername() {
		$initialProperties = [
			'Id' => 1,
			'Password' => 'password' // Email intentionally omitted
		];

		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\ModelException');
		$this->expectExceptionMessage('The username property Email must be defined in the properties array.');

		$user = new ConcreteUser(
			$initialProperties,
			[
				'Id' => 0,
				// 'Email' omitted for this test
				'Password' => ''
			]
		);
	}


	public function testUnsetPassword() {
		$initialProperties = [
			'Id' => 1,
			'Email' => 'joe@fluxoft.com' // Password intentionally omitted
		];

		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\ModelException');
		$this->expectExceptionMessage('The password property Password must be defined in the properties array.');

		$user = new ConcreteUser(
			$initialProperties,
			[
				'Id' => 0,
				'Email' => '',
				// 'Password' omitted for this test
			]
		);
	}

	public function testPropertyAccessors() {
		$user = new ConcreteUser([
			'Id' => 1,
			'Email' => 'joe@fluxoft.com',
			'Password' => 'password'
		]);

		/*
		 * This is used by the UserMapper in case someone has set
		 * a different property name as their username property.
		 */
		$this->assertEquals('Email', $user->GetAuthUsernameProperty());

		$this->assertEquals(1, $user->Id);
		$this->assertEquals('joe@fluxoft.com', $user->Email);

		/*
		 * Do not allow retrieving the actual password.
		 */
		$this->assertEquals('********', $user->Password);

		// Set a password and confirm it was hashed correctly.
		$newPassword      = 'VerySecurePassword';
		$user->Password = $newPassword;

		// test IsValid function for new password
		$this->assertTrue($user->IsPasswordValid($newPassword));
	}
}

// @codingStandardsIgnoreStart
class ConcreteUser extends User {
	// @codingStandardsIgnoreEnd
	protected $authUsernameProperty = 'Email';
	protected $authPasswordProperty = 'Password';

	public function __construct(
		array $initialProperties = [],
		array $defaultProperties    = null,
		string $authUsernameProperty = null,
		string $authPasswordProperty = null
	) {
		// Allow overriding default properties for testing
		if (isset($defaultProperties)) {
			self::$defaultProperties = $defaultProperties;
		}

		// Allow overriding username and password properties for testing
		if (isset($authUsernameProperty)) {
			$this->authUsernameProperty = $authUsernameProperty;
		}
		if (isset($authPasswordProperty)) {
			$this->authPasswordProperty = $authPasswordProperty;
		}

		parent::__construct($initialProperties);
	}

	public function PublicGenerateHash($password, $cost = 11) {
		return $this->generateHash($password, $cost);
	}
	public function GetRealPassword() {
		return $this->properties[$this->authPasswordProperty];
	}

	protected static $defaultProperties = [
		'Id' => 0,
		'Email' => '',
		'Password' => ''
	];
}
