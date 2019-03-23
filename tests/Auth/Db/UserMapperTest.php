<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Db\MapperFactory;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $connectionObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $statementObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $userModelObserver;
	/** @var MapperFactory */
	private $mapperFactory;

	public function setup() {
		$this->connectionObserver = $this->getMockBuilder('\Doctrine\DBAL\Connection')
			->disableOriginalConstructor()
			->getMock();
		$this->statementObserver  = $this->getMockBuilder('\Doctrine\DBAL\Statement')
			->disableOriginalConstructor()
			->getMock();
		$this->userModelObserver  = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Db\ConcreteUserForUserMapperTest')
			->disableOriginalConstructor()
			->getMock();

		$this->mapperFactory = new ConcreteMapperFactoryForUserMapperTest(
			$this->connectionObserver
		);
	}

	public function tearDown() {
		unset($this->userModelObserver);
		unset($this->statementObserver);
		unset($this->connectionObserver);
	}

	/**
	 * @param User|mixed $returnUser
	 * @param string $password
	 * @dataProvider authorizedUserProvider
	 */
	public function testGetAuthorizedUserForUsernameAndPassword(
		$returnUser,
		string $password
	) {
		$userMapper = $this->getMockBuilder('Fluxoft\Rebar\Auth\Db\ConcreteUserMapper')
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionObserver
			])
			->setMethods(['GetOneWhere'])
			->getMock();

		$userMapper
			->expects($this->once())
			->method('GetOneWhere')
			->will($this->returnValue($returnUser));
		$this->userModelObserver
			->expects($this->once())
			->method('GetAuthUsernameProperty')
			->will($this->returnValue('Username'));

		$validUser = false;
		if (isset($returnUser)) {
			if ($returnUser->IsPasswordValid($password)) {
				$validUser = true;
			} else {
				$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException');
				$this->expectExceptionMessage('Incorrect password');
			}
		} else {
			$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException');
			$this->expectExceptionMessage('User not found');
		}

		$authorizedUser = $userMapper->GetAuthorizedUserForUsernameAndPassword(null, $password);
		if ($validUser) {
			$this->assertEquals($returnUser, $authorizedUser);
		}
	}
	public function authorizedUserProvider() {
		$user           = new ConcreteUserForUserMapperTest();
		$user->Password = 'password';
		return [
			[
				'returnUser' => null,
				'password' => 'invalid'
			],
			[
				'returnUser' => $user,
				'password' => 'invalid'
			],
			[
				'returnUser' => $user,
				'password' => 'password'
			]
		];
	}

	public function testGetAuthorizedUserById() {
		$userMapper = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Db\UserMapper')
			->disableOriginalConstructor()
			->setMethods(['GetOneById'])
			->getMock();
		$user       = new ConcreteUserForUserMapperTest();

		$userMapper
			->expects($this->once())
			->method('GetOneById')
			->will($this->returnValue($user));

		$authorizedUser = $userMapper->GetAuthorizedUserById(1);

		$this->assertEquals($authorizedUser, $user);
	}
}

// @codingStandardsIgnoreStart
class ConcreteUserMapper extends UserMapper {}
class ConcreteMapperFactoryForUserMapperTest extends MapperFactory {}
class ConcreteUserForUserMapperTest extends User {
	// @codingStandardsIgnoreEnd
	protected $properties    = [
		'Id' => 1,
		'Email' => 'joe@fluxoft.com',
		'Password' => 'password'
	];
	protected $propertyDbMap = [
		'Id' => 'id',
		'Email' => 'email',
		'Password' => 'password'
	];
	protected $dbTable       = 'users';
}
