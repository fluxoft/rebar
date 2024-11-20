<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;
use PHPUnit\Framework\TestCase;

class UserMapperTest extends TestCase {
	private $connectionMock;
	private $statementMock;
	
	/** @var ConcreteUserForUserMapperTest */
	private ConcreteUserForUserMapperTest $userModelObserver;
	/** @var ConcreteMapperFactoryForUserMapperTest */
	private ConcreteMapperFactoryForUserMapperTest $mapperFactory;

	public function setup(): void {
		// Mock PDO connection
		$this->connectionMock = $this->createMock(\PDO::class);
	
		// Mock the PDOStatement
		$this->statementMock = $this->createMock(\PDOStatement::class);
		$this->statementMock->method('fetch')
			->willReturn([
				'Id' => 1,
				'Email' => 'joe@fluxoft.com',
				'Password' => password_hash('password', PASSWORD_BCRYPT)
			]);
		$this->statementMock->method('execute')
			->willReturn(true);
	
		// Mock the prepare method to return the PDOStatement mock
		$this->connectionMock->method('prepare')
			->willReturn($this->statementMock);
	
		// Initialize other dependencies
		$this->userModelObserver = new ConcreteUserForUserMapperTest();
		$this->mapperFactory     = $this->getMockBuilder(ConcreteMapperFactoryForUserMapperTest::class)
			->setConstructorArgs([$this->connectionMock])
			->getMock();
	}

	public function testGetAuthorizedUserForUsernameAndPassword() {
		// Arrange: Set up a valid user to be returned by GetOne
		$user = new ConcreteUserForUserMapperTest([
			'Id' => 23,
			'Email' => 'joe@fluxoft.com',
			'Password' => password_hash('password', PASSWORD_BCRYPT)
		]);
	
		$mapper = $this->getMockBuilder(ConcreteUserMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionMock
			])
			->onlyMethods(['GetOne'])
			->getMock();
	
		// Create the expected Filter
		$expectedFilter = new \Fluxoft\Rebar\Data\Db\Filter('Email', '=', 'joe@fluxoft.com');
	
		// Mock the GetOne method to return the user
		$mapper->expects($this->once())
			->method('GetOne')
			->with([$expectedFilter])
			->willReturn($user);
	
		// Act: Call the method to test
		$result = $mapper->GetAuthorizedUserForUsernameAndPassword('joe@fluxoft.com', 'password');

		echo "\n\n".var_dump($result)."\n\n";
		echo "\n\n$result->Id\n\n";
	
		// Assert: Verify the result
		$this->assertInstanceOf(ConcreteUserForUserMapperTest::class, $result);
		$this->assertEquals(1, $result->Id);
		$this->assertEquals('joe@fluxoft.com', $result->Email);
	}

	public function testGetAuthorizedUserById() {
		$mapper         = new ConcreteUserMapper($this->mapperFactory, $this->userModelObserver, $this->connectionMock);
		$user           = new ConcreteUserForUserMapperTest();
		$user->Password = password_hash('password', PASSWORD_BCRYPT);

		$mapper->Save($user);

		// Test valid ID
		$retrievedUser = $mapper->GetAuthorizedUserById(1);
		$this->assertEquals($user, $retrievedUser);

		// Test invalid ID
		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException');
		$this->expectExceptionMessage('User not found');
		$mapper->GetAuthorizedUserById(999);
	}
}

// @codingStandardsIgnoreStart
class ConcreteUserMapper extends GenericSql implements UserMapperInterface {
	use UserMapperTrait;

	protected array $propertyDbMap = [
		'Id'       => 'id',
		'Email'    => 'email',
		'Password' => 'password'
	];
	protected string $dbTable = 'users';
}

class ConcreteMapperFactoryForUserMapperTest extends MapperFactory {}

class ConcreteUserForUserMapperTest extends User {
	protected static $defaultProperties = [
		'Id'       => 1,
		'Email'    => 'joe@fluxoft.com',
		'Password' => null
	];
}
// @codingStandardsIgnoreEnd
