<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\RefreshToken;
use Fluxoft\Rebar\Auth\RefreshTokenMapperInterface;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefreshTokenMapperTest extends TestCase {
	private $connectionMock;
	private $statementMock;

	/** @var RefreshToken */
	private RefreshToken $refreshTokenModelObserver;
	/** @var ConcreteMapperFactoryForRefreshTokenMapperTest */
	private ConcreteMapperFactoryForRefreshTokenMapperTest $mapperFactory;

	public function setUp(): void {
		// Mock PDO connection
		$this->connectionMock = $this->createMock(\PDO::class);

		// Mock the PDOStatement
		$this->statementMock = $this->createMock(\PDOStatement::class);
		$this->statementMock->method('fetch')
			->willReturn([
				'Id' => 1,
				'UserId' => 1,
				'SeriesId' => 12345,
				'Token' => 'abcde12345',
				'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
				'RevokedAt' => null
			]);
		$this->statementMock->method('execute')
			->willReturn(true);

		// Mock the prepare method to return the PDOStatement mock
		$this->connectionMock->method('prepare')
			->willReturn($this->statementMock);

		// Initialize other dependencies
		$this->refreshTokenModelObserver = new RefreshToken();
		$this->mapperFactory             = $this->getMockBuilder(ConcreteMapperFactoryForRefreshTokenMapperTest::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetRefreshToken() {
		$userId        = 1;
		$seriesId      = 123;
		$token         = 'sampleToken';
		$expectedToken = new DummyRefreshTokenModel([
			'UserId' => $userId,
			'SeriesId' => $seriesId,
			'Token' => $token,
			'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
			'RevokedAt' => null,
		]);
	
		// Mock the RefreshTokenMapper
		/** @var ConcreteRefreshTokenMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteRefreshTokenMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				new DummyRefreshTokenModel(), // Use the appropriate model
				$this->connectionMock
			])
			->onlyMethods(['GetOne'])
			->getMock();
	
		// Expect the `GetOne` method to be called with the correct filters
		$mapper->expects($this->once())
			->method('GetOne')
			->with($this->callback(function ($filters) use ($userId, $seriesId, $token) {
				return count($filters) === 5 &&
					$filters[0]->Property === 'UserId' && $filters[0]->Value === $userId &&
					$filters[1]->Property === 'SeriesId' && $filters[1]->Value === $seriesId &&
					$filters[2]->Property === 'Token' && $filters[2]->Value === $token &&
					$filters[3]->Property === 'ExpiresAt' && $filters[3]->Operator === '>' &&
					$filters[4]->Property === 'RevokedAt' && $filters[4]->Operator === 'IS';
			}))
			->willReturn($expectedToken);
	
		// Act
		$result = $mapper->GetRefreshToken($userId, $seriesId, $token, true);
	
		// Assert
		$this->assertEquals($expectedToken, $result);
	}

	public function testGetRefreshTokensByUserId() {
		$userId         = 1;
		$expectedTokens = [
			new DummyRefreshTokenModel([
				'UserId' => $userId,
				'SeriesId' => 123,
				'Token' => 'token1',
				'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
				'RevokedAt' => null,
			]),
			new DummyRefreshTokenModel([
				'UserId' => $userId,
				'SeriesId' => 124,
				'Token' => 'token2',
				'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+2 days')),
				'RevokedAt' => null,
			])
		];
	
		/** @var ConcreteRefreshTokenMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteRefreshTokenMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				new DummyRefreshTokenModel(),
				$this->connectionMock
			])
			->onlyMethods(['GetSet'])
			->getMock();
	
		$mapper->expects($this->once())
			->method('GetSet')
			->with($this->callback(function ($filters) use ($userId) {
				return count($filters) === 1 &&
					$filters[0]->Property === 'UserId' &&
					$filters[0]->Value === $userId;
			}))
			->willReturn($expectedTokens);
	
		// Act
		$result = $mapper->GetRefreshTokensByUserId($userId);
	
		// Assert
		$this->assertEquals($expectedTokens, $result);
	}

	public function testSaveRefreshToken() {
		$refreshToken = new DummyRefreshTokenModel([
			'UserId' => 1,
			'SeriesId' => 123,
			'Token' => 'token1',
			'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
			'RevokedAt' => null,
		]);
	
		/** @var ConcreteRefreshTokenMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteRefreshTokenMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				new DummyRefreshTokenModel(),
				$this->connectionMock
			])
			->onlyMethods(['Save'])
			->getMock();
	
		$mapper->expects($this->once())
			->method('Save')
			->with($refreshToken);
	
		// Act
		$mapper->SaveRefreshToken($refreshToken);
	
		// Assert
		$this->addToAssertionCount(1); // Verify Save was called without exceptions
	}

	public function testRevokeRefreshToken() {
		$refreshToken = new DummyRefreshTokenModel([
			'UserId' => 1,
			'SeriesId' => 123,
			'Token' => 'token1',
			'ExpiresAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
			'RevokedAt' => null,
		]);
	
		/** @var ConcreteRefreshTokenMapper|MockObject $mapper */
		$mapper = $this->getMockBuilder(ConcreteRefreshTokenMapper::class)
			->setConstructorArgs([
				$this->mapperFactory,
				new DummyRefreshTokenModel(),
				$this->connectionMock
			])
			->onlyMethods(['Save'])
			->getMock();
	
		$mapper->expects($this->once())
			->method('Save')
			->with($this->callback(function (DummyRefreshTokenModel $token) use ($refreshToken) {
				return $token->RevokedAt !== null &&
					$token->ExpiresAt === $token->RevokedAt &&
					$token->UserId === $refreshToken->UserId &&
					$token->SeriesId === $refreshToken->SeriesId &&
					$token->Token === $refreshToken->Token;
			}));
	
		// Act
		$mapper->RevokeRefreshToken($refreshToken);
	
		// Assert
		$this->addToAssertionCount(1); // Verify Save was called without exceptions
	}

	public function testEnforceMapperRequirementsThrowsExceptionForMissingRefreshTokenMapperInterface() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The class %s must implement %s to use %s.',
			DummyRefreshTokenMapper_NoRefreshTokenMapperInterface::class,
			RefreshTokenMapperInterface::class,
			RefreshTokenMapperTrait::class
		));

		$dummy = new DummyRefreshTokenMapper_NoRefreshTokenMapperInterface(
			$this->mapperFactory,
			new DummyRefreshTokenModel(),
			$this->connectionMock
		);

		$dummy->GetRefreshToken(1, 12345, 'abcde12345', true);
	}

	public function testEnforceMapperRequiresThrowsExceptionForMissingMapperInterface() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The class %s must implement %s to use %s.',
			DummyRefreshTokenMapper_NoMapperInterface::class,
			\Fluxoft\Rebar\Data\Db\Mappers\MapperInterface::class,
			RefreshTokenMapperTrait::class
		));

		$dummy = new DummyRefreshTokenMapper_NoMapperInterface(
			$this->mapperFactory,
			new DummyRefreshTokenModel(),
			$this->connectionMock
		);

		$dummy->GetRefreshToken(1, 12345, 'abcde12345', true);
	}

	public function testEnforceMapperRequirementsThrowsExceptionForInvalidModel() {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage(sprintf(
			'The model for the class %s must be an instance of %s to use %s.',
			DummyRefreshTokenMapperWithoutRefreshToken::class,
			RefreshToken::class,
			RefreshTokenMapperTrait::class
		));

		$dummy = new DummyRefreshTokenMapperWithoutRefreshToken(
			$this->mapperFactory,
			new DummyInvalidModel(),
			$this->connectionMock
		);

		$dummy->GetRefreshToken(1, 12345, 'abcde12345', true);
	}
}

// Dummy classes
// @codingStandardsIgnoreStart
class ConcreteRefreshTokenMapper extends GenericSql implements RefreshTokenMapperInterface {
	use RefreshTokenMapperTrait;

	protected array $propertyDbMap = [
		'Id' => 'id',
		'UserId' => 'user_id',
		'SeriesId' => 'series_id',
		'Token' => 'token',
		'ExpiresAt' => 'expires_at',
		'RevokedAt' => 'revoked_at',
	];
	protected string $dbTable = 'refresh_tokens';
}

class ConcreteMapperFactoryForRefreshTokenMapperTest extends MapperFactory {}

class DummyRefreshTokenModel extends RefreshToken {
    protected static array $defaultProperties = [
        'Id' => null,
        'UserId' => null,
        'SeriesId' => null,
        'Token' => null,
        'ExpiresAt' => null,
        'RevokedAt' => null,
    ];
}

class DummyInvalidModel extends Model {}

class DummyRefreshTokenMapper_NoRefreshTokenMapperInterface extends GenericSql {
	use RefreshTokenMapperTrait;

	protected array $propertyDbMap = [
		'Id' => 'id',
		'UserId' => 'user_id',
		'SeriesId' => 'series_id',
		'Token' => 'token',
		'ExpiresAt' => 'expires_at',
		'RevokedAt' => 'revoked_at',
	];
	protected string $dbTable = 'refresh_tokens';
}

class DummyRefreshTokenMapper_NoMapperInterface implements RefreshTokenMapperInterface {
	use RefreshTokenMapperTrait;

	protected array $propertyDbMap = [
		'Id' => 'id',
		'UserId' => 'user_id',
		'SeriesId' => 'series_id',
		'Token' => 'token',
		'ExpiresAt' => 'expires_at',
		'RevokedAt' => 'revoked_at',
	];
	protected string $dbTable = 'refresh_tokens';
}

class DummyRefreshTokenMapperWithoutRefreshToken extends GenericSql implements RefreshTokenMapperInterface {
	use RefreshTokenMapperTrait;

	protected array $propertyDbMap = [
		'Id' => 'id',
		'UserId' => 'user_id',
		'SeriesId' => 'series_id',
		'Token' => 'token',
		'ExpiresAt' => 'expires_at',
		'RevokedAt' => 'revoked_at',
	];
	protected string $dbTable = 'refresh_tokens';
}
// @codingStandardsIgnoreEnd
