<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\BaseUser;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSqlMapper;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
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
			'Id'       => 1,
			'Email'    => 'joe@fluxoft.com',
			'Password' => password_hash('password', PASSWORD_BCRYPT)
		]);

		/** @var ConcreteUserMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteUserMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionMock
			])
			->onlyMethods(['GetOne']) // Mock only the method actually being used
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

		// Assert: Verify the result
		$this->assertInstanceOf(ConcreteUserForUserMapperTest::class, $result);
		$this->assertEquals(1, $result->Id);
		$this->assertEquals('joe@fluxoft.com', $result->Email);
		$this->assertEquals('********', $result->Password);
	}

	public function testGetAuthorizedUserById() {
		// Arrange: Set up a valid user to be returned by GetOneById
		$user = new ConcreteUserForUserMapperTest([
			'Id'       => 1,
			'Email'    => 'joe@fluxoft.com',
			'Password' => password_hash('password', PASSWORD_BCRYPT)
		]);

		/** @var ConcreteUserMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteUserMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionMock
			])
			->onlyMethods(['GetOneById'])
			->getMock();

		// Mock the GetOneById method to return the user for a valid ID
		$mapper->expects($this->exactly(2)) // One for valid and one for invalid test case
			->method('GetOneById')
			->willReturnCallback(function ($id) use ($user) {
				return $id === 1 ? $user : null;
			});

		// Act & Assert: Test valid ID
		$result = $mapper->GetAuthorizedUserById(1);
		$this->assertInstanceOf(ConcreteUserForUserMapperTest::class, $result);
		$this->assertEquals(1, $result->Id);
		$this->assertEquals('joe@fluxoft.com', $result->Email);

		// Act & Assert: Test invalid ID
		$this->expectException('\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException');
		$this->expectExceptionMessage('User not found');
		$mapper->GetAuthorizedUserById(999);
	}

	public function testEnforceMapperRequirementsThrowsExceptionForMissingUserMapperInterface() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The class %s must implement %s to use %s.',
			DummyUserMapperWithoutUserMapperInterface::class,
			\Fluxoft\Rebar\Auth\UserMapperInterface::class,
			\Fluxoft\Rebar\Auth\Db\UserMapperTrait::class
		));

		$dummy = new DummyUserMapperWithoutUserMapperInterface(
			$this->mapperFactory,
			new DummyUserModel(),
			$this->connectionMock
		);
		$dummy->GetAuthorizedUserForUsernameAndPassword('joe@fluxoft.com', 'password');
	}

	public function testEnforcemapperRequirementsThrowsExceptionForMissingMapperInterface() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The class %s must implement %s to use %s.',
			DummyUserMapperWithoutMapperInterface::class,
			\Fluxoft\Rebar\Data\Db\Mappers\MapperInterface::class,
			\Fluxoft\Rebar\Auth\Db\UserMapperTrait::class
		));

		$dummy = new DummyUserMapperWithoutMapperInterface(
			$this->mapperFactory,
			new DummyUserModel(),
			$this->connectionMock
		);
		$dummy->GetAuthorizedUserForUsernameAndPassword('joe@fluxoft.com', 'password');
	}

	public function testEnforceMapperRequirementsThrowsExceptionForInvalidUserModel() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The class %s must implement %s to use %s.',
			DummyUserMapperWithoutUserInterface::class,
			\Fluxoft\Rebar\Auth\UserInterface::class,
			\Fluxoft\Rebar\Auth\Db\UserMapperTrait::class
		));

		$dummy = new DummyUserMapperWithoutUserInterface(
			$this->mapperFactory,
			new DummyUserModel(),
			$this->connectionMock
		);
		$dummy->GetAuthorizedUserForUsernameAndPassword('joe@fluxoft.com', 'password');
	}

	public function testGetAuthorizedUserForUsernameAndPasswordThrowsExceptionForInvalidPassword() {
		$this->expectException(\Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException::class);
		$this->expectExceptionMessage('Incorrect password');

		$user = new ConcreteUserForUserMapperTest([
			'Id' => 1,
			'Email' => 'joe@fluxoft.com',
			'Password' => password_hash('correct_password', PASSWORD_BCRYPT)
		]);

		/** @var ConcreteUserMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteUserMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionMock
			])
			->onlyMethods(['GetOne'])
			->getMock();

		$mapper->expects($this->once())
			->method('GetOne')
			->willReturn($user);

		$mapper->GetAuthorizedUserForUsernameAndPassword('joe@fluxoft.com', 'wrong_password');
	}

	public function testGetAuthorizedUserForUsernameAndPasswordThrowsExceptionForUserNotFound() {
		$this->expectException(\Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException::class);
		$this->expectExceptionMessage('User not found');

		/** @var ConcreteUserMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteUserMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				$this->userModelObserver,
				$this->connectionMock
			])
			->onlyMethods(['GetOne'])
			->getMock();

		$mapper->expects($this->once())
			->method('GetOne')
			->willReturn(null); // Simulate user not found

		$mapper->GetAuthorizedUserForUsernameAndPassword('nonexistent@fluxoft.com', 'password');
	}
}

// @codingStandardsIgnoreStart
class ConcreteUserMapper extends GenericSqlMapper implements UserMapperInterface {
	use UserMapperTrait;

	protected array $propertyDbMap = [
		'Id'       => 'id',
		'Email'    => 'email',
		'Password' => 'password'
	];
	protected string $dbTable = 'users';
}

class ConcreteMapperFactoryForUserMapperTest extends MapperFactory {}

class ConcreteUserForUserMapperTest extends BaseUser {
	protected static array $defaultProperties = [
		'Id'       => 1,
		'Email'    => 'joe@fluxoft.com',
		'Password' => null
	];
	protected string $authUsernameProperty = 'Email';
}

// Dummy class for testing UserMapperTrait behaviors without mocking complexity
class DummyUserModel extends Model {
    protected static array $defaultProperties = [
        'Id'       => null,
        'Email'    => null,
        'Password' => null,
    ];
}

class DummyUserMapperWithoutUserMapperInterface extends GenericSqlMapper {
	use UserMapperTrait;

    protected array $propertyDbMap = [
        'Id'       => 'id',
        'Email'    => 'email',
        'Password' => 'password',
    ];
    protected string $dbTable = 'users';
}

class DummyUserMapperWithoutMapperInterface implements UserMapperInterface {
	use UserMapperTrait;

	protected array $propertyDbMap = [
		'Id'       => 'id',
		'Email'    => 'email',
		'Password' => 'password',
	];
	protected string $dbTable = 'users';
}

class DummyUserMapperWithoutUserInterface extends GenericSqlMapper implements UserMapperInterface {
    use UserMapperTrait;

    protected array $propertyDbMap = [
        'Id'       => 'id',
        'Email'    => 'email',
        'Password' => 'password',
    ];
    protected string $dbTable = 'users';
}
// @codingStandardsIgnoreEnd
