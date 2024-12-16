<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\Exceptions\MapperException;
use Fluxoft\Rebar\Data\Db\Filter;
use Fluxoft\Rebar\Data\Db\Join;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use Fluxoft\Rebar\Data\Db\Property;
use Fluxoft\Rebar\Data\Db\Sort;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GenericSqlTest extends TestCase {
	protected ConcreteGenericSql $mapper;

	private $mapperFactory;
	private $model;
	private $reader;
	private $writer;

	protected function setUp(): void {
		/** @var MapperFactory $mapperFactory */
		$this->mapperFactory = $this->createMock(MapperFactory::class);
		/** @var Model $model */
		$this->model = new ConcreteModel();
		/** @var \PDO $reader */
		$this->reader = $this->createMock(\PDO::class);
		/** @var \PDO|MockObject $writer */
		$this->writer = $this->createMock(\PDO::class);

		$this->mapper = new ConcreteGenericSql(
			$this->mapperFactory,
			$this->model,
			$this->reader,
			$this->writer
		);
	}

	public function testConstructorConvertsLegacyFormats() {
		$reflection    = new \ReflectionClass($this->mapper);
		$propertyDbMap = $reflection->getProperty('propertyDbMap');
		$propertyDbMap->setAccessible(true);
		$dbMap = $propertyDbMap->getValue($this->mapper);

		$this->assertInstanceOf(Property::class, $dbMap['Id']);
		$this->assertInstanceOf(Property::class, $dbMap['Username']);
		$this->assertInstanceOf(Property::class, $dbMap['Password']);
	}

	public function testHasAggregatesWithAggregateProperty() {
		$this->mapper->SetPropertyDbMap([
			'Id' => 'id',
			'AggregateProperty' => new Property('COUNT(id)', 'integer')
		]);

		$result = $this->mapper->PublicHasAggregatesInSelect([]);
		$this->assertTrue(
			$result,
			'Expected true when propertyDbMap contains an aggregate property.'
		);
	}
	public function testHasAggregatesWithNonAggregateProperty() {
		$result = $this->mapper->PublicHasAggregatesInSelect([]);
		$this->assertFalse(
			$result,
			'Expected false when propertyDbMap contains no aggregate properties.'
		);
	}

	/**
	 * @param Filter[] $filters
	 * @param Sort[] $sort
	 * @param int $page
	 * @param int $pageSize
	 * @param array{sql: string, params: array} $expectedQuery
	 * @param string|null $expectedExceptionClass
	 * @param string|null $expectedExceptionMessage
	 *
	 * @dataProvider getSelectQueryProvider
	 */
	public function testGetSelectQuery(
		array $filters,
		array $sort,
		array $joins,
		int $page,
		int $pageSize,
		array $expectedQuery,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
		?callable $customSetup = null
	) {
		if ($customSetup) {
			$customSetup($this->mapper);
		}

		if ($expectedExceptionClass) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}

		$this->mapper->SetJoins($joins);

		$select = $this->mapper->PublicGetSelect($filters, $sort, $page, $pageSize);

		if (!$expectedExceptionClass) {
			$this->assertEquals($expectedQuery['sql'], $select['sql']);
			$this->assertEquals($expectedQuery['params'], $select['params']);
		}
	}
	// @codingStandardsIgnoreStart
	public function getSelectQueryProvider(): array {
		return [
			'No filters, no sort, page 1, page size 10' => [
				'filters' => [],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate '.
							'FROM users LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'With filters, no sort, page 1, page size 5' => [
				'filters' => [new Filter('Username', '=', 'testuser')],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 5,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate '.
							'FROM users WHERE users.username = :Username LIMIT 5 OFFSET 0',
					'params' => [':Username' => 'testuser']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Invalid page size' => [
				'filters' => [],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => -1,
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'Page size must be a positive integer.'
			],
			'Multiple filters, no sort, page 1, page size 10' => [
				'filters' => [
					new Filter('Username', '=', 'testuser'),
					new Filter('Status', '=', 'active')
				],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate '.
							'FROM users WHERE users.username = :Username AND users.status = :Status LIMIT 10 OFFSET 0',
					'params' => [':Username' => 'testuser', ':Status' => 'active']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Sort by single column, page 1, page size 10' => [
				'filters' => [],
				'sort' => [new Sort('Username', 'ASC')],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate '.
							'FROM users ORDER BY users.username ASC LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Sort by multiple columns, page 1, page size 10' => [
				'filters' => [],
				'sort' => [
					new Sort('Username', 'ASC'),
					new Sort('Id', 'DESC')
				],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate '.
							'FROM users ORDER BY users.username ASC, users.id DESC LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Filter with aggregate column (HAVING), page 1, page size 10' => [
				'filters' => [new Filter('COUNT(Id)', '>', 5)],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [],
				'expectedExceptionClass' => MapperException::class,
				'expectedExceptionMessage' => 'Trying to filter on a non-property: COUNT(Id)'
			],
			'Invalid page number' => [
				'filters' => [],
				'sort' => [],
				'joins' => [],
				'page' => 0,
				'pageSize' => 10,
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'Page number must be at least 1.'
			],
			'No pagination (retrieve all)' => [
				'filters' => [],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 0,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Filter with IN clause' => [
				'filters' => [new Filter('Username', 'IN', ['user1', 'user2', 'user3'])],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'WHERE users.username IN (:Username_0, :Username_1, :Username_2) LIMIT 10 OFFSET 0',
					'params' => [
						':Username_0' => 'user1',
						':Username_1' => 'user2',
						':Username_2' => 'user3'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Filter with BETWEEN clause' => [
				'filters' => [new Filter('Id', 'BETWEEN', [1, 100])],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'WHERE users.id BETWEEN :Id_min AND :Id_max LIMIT 10 OFFSET 0',
					'params' => [':Id_min' => 1, ':Id_max' => 100]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Multiple filters, no sort, page 1, page size 10' => [
				'filters' => [
					new Filter('Username', '=', 'testuser'),
					new Filter('Password', 'LIKE', '%password%')
				],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'WHERE users.username = :Username AND users.password LIKE :Password LIMIT 10 OFFSET 0',
					'params' => [':Username' => 'testuser', ':Password' => '%password%']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Single column sort, no filters, page 1, page size 10' => [
				'filters' => [],
				'sort' => [new Sort('Username', 'ASC')],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'ORDER BY users.username ASC LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Multiple column sort, no filters, page 1, page size 10' => [
				'filters' => [],
				'sort' => [
					new Sort('Username', 'ASC'),
					new Sort('Id', 'DESC')
				],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'ORDER BY users.username ASC, users.id DESC LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Page 2, no page size' => [
				'filters' => [],
				'sort' => [],
				'joins' => [],
				'page' => 2,
				'pageSize' => 0,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Between filter, no sort, page 1, page size 10' => [
				'filters' => [new Filter('CreatedDate', 'BETWEEN', ['2023-01-01', '2023-12-31'])],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'WHERE users.created_date BETWEEN :CreatedDate_min AND :CreatedDate_max LIMIT 10 OFFSET 0',
					'params' => [':CreatedDate_min' => '2023-01-01', ':CreatedDate_max' => '2023-12-31']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'In filter, no sort, page 1, page size 10' => [
				'filters' => [new Filter('Username', 'IN', ['user1', 'user2', 'user3'])],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'WHERE users.username IN (:Username_0, :Username_1, :Username_2) LIMIT 10 OFFSET 0',
					'params' => [':Username_0' => 'user1', ':Username_1' => 'user2', ':Username_2' => 'user3']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Single INNER JOIN' => [
				'filters' => [],
				'sort' => [],
				'joins' => [
					new Join('INNER', 'orders', 'users.id = orders.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'INNER JOIN orders ON users.id = orders.user_id LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Multiple JOINs' => [
				'filters' => [],
				'sort' => [],
				'joins' => [
					new Join('LEFT', 'orders', 'users.id = orders.user_id'),
					new Join('RIGHT', 'profiles', 'users.id = profiles.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate FROM users '.
							'LEFT JOIN orders ON users.id = orders.user_id RIGHT JOIN profiles ON users.id = profiles.user_id LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Aggregates with GROUP BY' => [
				'filters' => [],
				'sort' => [],
				'joins' => [
					new Join('LEFT', 'orders', 'users.id = orders.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, COUNT(orders.id) AS OrderCount FROM users LEFT JOIN orders ON users.id = orders.user_id GROUP BY users.id, users.username LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer')
					]);
				}
			],
			'Joined table with specific columns' => [
				'filters' => [],
				'sort' => [],
				'joins' => [
					new Join('INNER', 'groups', 'users.id = groups.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, groups.name AS GroupName FROM users '.
							'INNER JOIN groups ON users.id = groups.user_id LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'GroupName' => new Property('groups.name', 'string') // Explicit table reference
					]);
				}
			],
			'Aggregates and joined columns with GROUP BY' => [
				'filters' => [],
				'sort' => [],
				'joins' => [
					new Join('INNER', 'groups', 'users.id = groups.user_id'),
					new Join('LEFT', 'orders', 'users.id = orders.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, groups.name AS GroupName, COUNT(orders.id) AS OrderCount ' .
							'FROM users INNER JOIN groups ON users.id = groups.user_id ' .
							'LEFT JOIN orders ON users.id = orders.user_id ' .
							'GROUP BY users.id, users.username, groups.name LIMIT 10 OFFSET 0',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'GroupName' => new Property('groups.name', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer') // Aggregate
					]);
				}
			],
			'Filter on aggregate property (HAVING clause)' => [
				'filters' => [new Filter('OrderCount', '>', 10)],
				'sort' => [],
				'joins' => [
					new Join('LEFT', 'orders', 'users.id = orders.user_id')
				],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, COUNT(orders.id) AS OrderCount FROM users '.
							'LEFT JOIN orders ON users.id = orders.user_id GROUP BY users.id, users.username '.
							'HAVING OrderCount > :OrderCount LIMIT 10 OFFSET 0',
					'params' => [':OrderCount' => 10]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer') // Aggregate
					]);
				}
			],
			'Aliased subquery in SELECT' => [
				'filters' => [new Filter('OrderCount', '>', 10)],
				'sort' => [],
				'joins' => [],
				'page' => 1,
				'pageSize' => 10,
				'expectedQuery' => [
					'sql' => 'SELECT users.id AS Id, users.username AS Username, users.password AS Password, users.created_date AS CreatedDate, '.
							'(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) AS OrderCount '.
							'FROM users HAVING OrderCount > :OrderCount LIMIT 10 OFFSET 0',
					'params' => [':OrderCount' => 10]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'Password' => new Property('password', 'string'),
						'CreatedDate' => new Property('created_date', 'datetime'),
						'OrderCount' => new Property('(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id)', 'integer')
					]);
				}
			],

			// Add more edge cases as needed
		];
	}
	// @codingStandardsIgnoreEnd

	/**
	 * @param Filter[] $filters
	 * @param Join[] $joins
	 * @param array{sql: string, params: array} $expectedQuery
	 * @param string|null $expectedExceptionClass
	 * @param string|null $expectedExceptionMessage
	 *
	 * @dataProvider getCountSelectProvider
	 */
	public function testGetCountSelect(
		array $filters,
		array $joins,
		array $expectedQuery,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage
	) {
		if ($expectedExceptionClass) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}

		$this->mapper->SetJoins($joins);

		$countQuery = $this->mapper->PublicGetCount($filters);

		if (!$expectedExceptionClass) {
			$this->assertEquals($expectedQuery['sql'], $countQuery['sql']);
			$this->assertEquals($expectedQuery['params'], $countQuery['params']);
		}
	}
	// @codingStandardsIgnoreStart
	public function getCountSelectProvider(): array {
		return [
			'No filters' => [
				'filters' => [],
				'joins' => [],
				'expectedQuery' => [
					'sql' => 'SELECT COUNT(*) AS count FROM users',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'With valid filter' => [
				'filters' => [new Filter('Username', '=', 'testuser')],
				'joins' => [],
				'expectedQuery' => [
					'sql' => 'SELECT COUNT(*) AS count FROM users WHERE users.username = :Username',
					'params' => [':Username' => 'testuser']
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Filter with aggregate column' => [
				'filters' => [new Filter('COUNT(Id)', '>', 5)],
				'joins' => [],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to filter on a non-property: COUNT(Id)'
			],
			'Invalid filter property' => [
				'filters' => [new Filter('NonExistentProperty', '=', 'value')],
				'joins' => [],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to filter on a non-property: NonExistentProperty'
			],
			'Single INNER JOIN' => [
				'filters' => [],
				'joins' => [
					new Join('INNER', 'orders', 'users.id = orders.user_id')
				],
				'expectedQuery' => [
					'sql' => 'SELECT COUNT(*) AS count FROM users INNER JOIN orders ON users.id = orders.user_id',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Multiple JOINs' => [
				'filters' => [],
				'joins' => [
					new Join('LEFT', 'orders', 'users.id = orders.user_id'),
					new Join('RIGHT', 'profiles', 'users.id = profiles.user_id')
				],
				'expectedQuery' => [
					'sql' => 'SELECT COUNT(*) AS count FROM users '.
						'LEFT JOIN orders ON users.id = orders.user_id RIGHT JOIN profiles ON users.id = profiles.user_id',
					'params' => []
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
		];
	}
	// @codingStandardsIgnoreEnd

	/**
	 * @param array $data
	 * @param array{sql: string, params: array} $expectedQuery
	 * @param string|null $expectedExceptionClass
	 * @param string|null $expectedExceptionMessage
	 * @param callable|null $customSetup
	 * @dataProvider getInsertQueryProvider
	 */
	public function testGetInsertQuery(
		array $data,
		array $expectedQuery,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
		?callable $customSetup = null
	) {
		if ($customSetup) {
			$customSetup($this->mapper);
		}

		if ($expectedExceptionClass) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}

		$insertQuery = $this->mapper->PublicGetInsert($data);

		if (!$expectedExceptionClass) {
			$this->assertEquals($expectedQuery['sql'], $insertQuery['sql']);
			$this->assertEquals($expectedQuery['params'], $insertQuery['params']);
		}
	}
	// Data provider for testGetInsertQuery
	// @codingStandardsIgnoreStart
	public function getInsertQueryProvider(): array {
		return [
			'Simple insert with valid data' => [
				'data' => [
					'Username' => 'testuser',
					'Password' => 'testpassword',
					'CreatedDate' => new \DateTime('2024-01-01 12:00:00')
				],
				'expectedQuery' => [
					'sql' => 'INSERT INTO users (username, password, created_date) VALUES (:username, :password, :created_date)',
					'params' => [
						':username' => 'testuser',
						':password' => 'testpassword',
						':created_date' => '2024-01-01 12:00:00'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Invalid property in data' => [
				'data' => [
					'InvalidProperty' => 'value'
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to insert a non-mapped property: InvalidProperty'
			],
			'Empty data' => [
				'data' => [],
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'No valid data provided for insert.'
			],
			'Insert with all writable fields' => [
				'data' => [
					'Id' => 1, // Writable fields include all those in propertyDbMap that are not aggregate or subquery
					'Username' => 'testuser',
					'Password' => 'testpassword'
				],
				'expectedQuery' => [
					'sql' => 'INSERT INTO users (id, username, password) VALUES (:id, :username, :password)',
					'params' => [
						':id' => 1,
						':username' => 'testuser',
						':password' => 'testpassword'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null
			],
			'Insert with a calculated field (should be skipped)' => [
				'data' => [
					'Username' => 'testuser',
					'Password' => 'testpassword',
					'OrderCount' => 5 // Aggregate field, should not be included
				],
				'expectedQuery' => [
					'sql' => 'INSERT INTO users (username, password) VALUES (:username, :password)',
					'params' => [
						':username' => 'testuser',
						':password' => 'testpassword'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'Password' => new Property('password', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer') // Aggregate
					]);
				}
			],
			'Insert with date, time, and datetime fields' => [
				'data' => [
					'Username' => 'testuser',
					'Password' => 'testpassword',
					'DateField' => new \DateTime('2024-01-01'),
					'TimeField' => new \DateTime('12:00:00'),
					'DateTimeField' => new \DateTime('2024-01-01 12:00:00')
				],
				'expectedQuery' => [
					'sql' => 'INSERT INTO users (username, password, date_field, time_field, datetime_field) VALUES (:username, :password, :date_field, :time_field, :datetime_field)',
					'params' => [
						':username' => 'testuser',
						':password' => 'testpassword',
						':date_field' => '2024-01-01',
						':time_field' => '12:00:00',
						':datetime_field' => '2024-01-01 12:00:00'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'Password' => new Property('password', 'string'),
						'DateField' => new Property('date_field', 'date'),
						'TimeField' => new Property('time_field', 'time'),
						'DateTimeField' => new Property('datetime_field', 'datetime')
					]);
				}
			],
			'Insert with DateTime set to invalid type' => [
				'data' => [
					'Username' => 'testuser',
					'Password' => 'testpassword',
					'DateField' => new \DateTime('2024-01-01 12:00:00')
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'Cannot format DateTime object as type: string',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'Password' => new Property('password', 'string'),
						'DateField' => new Property('date_field', 'string')
					]);
				}
			],
			'Insert with non-DateTime value' => [
				'data' => [
					'CreatedDate' => '2024-01-01 12:00:00'
				],
				'expectedQuery' => [
					'sql' => 'INSERT INTO users (created_date) VALUES (:created_date)',
					'params' => [
						':created_date' => '2024-01-01 12:00:00'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'CreatedDate' => new Property('created_date', 'datetime')
					]);
				}
			],
		];
	}
	// @codingStandardsIgnoreEnd

	/**
	 * @param array $data
	 * @param array $conditions
	 * @param array{sql: string, params: array} $expectedQuery
	 * @param string|null $expectedExceptionClass
	 * @param string|null $expectedExceptionMessage
	 * @dataProvider getUpdateQueryProvider
	 */
	public function testGetUpdateQuery(
		array $data,
		array $conditions,
		array $expectedQuery,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
		?callable $customSetup = null
	) {
		if ($customSetup) {
			$customSetup($this->mapper);
		}

		if ($expectedExceptionClass) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}

		$updateQuery = $this->mapper->PublicGetUpdate($data, $conditions);

		if (!$expectedExceptionClass) {
			$this->assertEquals($expectedQuery['sql'], $updateQuery['sql']);
			$this->assertEquals($expectedQuery['params'], $updateQuery['params']);
		}
	}
	// Data provider for testGetUpdateQuery
	public function getUpdateQueryProvider(): array {
		return [
			'Simple update with valid data and conditions' => [
				'data' => [
					'Username' => 'updateduser'
				],
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [
					'sql' => 'UPDATE users SET username = :username WHERE id = :condition_id',
					'params' => [
						':username' => 'updateduser',
						':condition_id' => 1
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'Password' => new Property('password', 'string')
					]);
				}
			],
			'Update with datetime field' => [
				'data' => [
					'CreatedDate' => new \DateTime('2024-01-01 12:00:00')
				],
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [
					'sql' => 'UPDATE users SET created_date = :created_date WHERE id = :condition_id',
					'params' => [
						':created_date' => '2024-01-01 12:00:00',
						':condition_id' => 1
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'CreatedDate' => new Property('created_date', 'datetime')
					]);
				}
			],
			'Update with invalid property in data' => [
				'data' => [
					'InvalidProperty' => 'value'
				],
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to update a non-mapped property: InvalidProperty',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
			'Update with no conditions' => [
				'data' => [
					'Username' => 'updateduser'
				],
				'conditions' => [],
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'No conditions provided for update.',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
			'Update skips non-writable fields' => [
				'data' => [
					'OrderCount' => 5, // Aggregate field, should be skipped
					'Username' => 'updateduser'
				],
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [
					'sql' => 'UPDATE users SET username = :username WHERE id = :condition_id',
					'params' => [
						':username' => 'updateduser',
						':condition_id' => 1
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer') // Aggregate field
					]);
				}
			],
			'No valid data provided for update' => [
				'data' => [
					'OrderCount' => 5 // Aggregate field, not writable
				],
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'No valid data provided for update.',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string'),
						'OrderCount' => new Property('COUNT(orders.id)', 'integer') // Non-writable
					]);
				}
			],
			'Unmapped property in conditions' => [
				'data' => [
					'Username' => 'updateduser'
				],
				'conditions' => [
					'NonExistentProperty' => 'value'
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to filter on a non-mapped property: NonExistentProperty',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
		];
	}

	/**
	 * @param array $conditions
	 * @param array{sql: string, params: array} $expectedQuery
	 * @param string|null $expectedExceptionClass
	 * @param string|null $expectedExceptionMessage
	 * @param callable|null $customSetup
	 * @dataProvider getDeleteQueryProvider
	 */
	public function testGetDeleteQuery(
		array $conditions,
		array $expectedQuery,
		?string $expectedExceptionClass,
		?string $expectedExceptionMessage,
		?callable $customSetup = null
	) {
		if ($customSetup) {
			$customSetup($this->mapper);
		}

		if ($expectedExceptionClass) {
			$this->expectException($expectedExceptionClass);
			$this->expectExceptionMessage($expectedExceptionMessage);
		}

		$deleteQuery = $this->mapper->PublicGetDelete($conditions);

		if (!$expectedExceptionClass) {
			$this->assertEquals($expectedQuery['sql'], $deleteQuery['sql']);
			$this->assertEquals($expectedQuery['params'], $deleteQuery['params']);
		}
	}
	// Data provider for testGetDeleteQuery
	public function getDeleteQueryProvider(): array {
		return [
			'Simple delete with valid condition' => [
				'conditions' => [
					'Id' => 1
				],
				'expectedQuery' => [
					'sql' => 'DELETE FROM users WHERE id = :id',
					'params' => [
						':id' => 1
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
			'Unmapped property in conditions' => [
				'conditions' => [
					'NonExistentProperty' => 'value'
				],
				'expectedQuery' => [],
				'expectedExceptionClass' => \Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class,
				'expectedExceptionMessage' => 'Trying to filter on a non-mapped property: NonExistentProperty',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
			'Empty conditions' => [
				'conditions' => [],
				'expectedQuery' => [],
				'expectedExceptionClass' => \InvalidArgumentException::class,
				'expectedExceptionMessage' => 'No conditions provided for delete.',
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
			'Multiple valid conditions' => [
				'conditions' => [
					'Id' => 1,
					'Username' => 'testuser'
				],
				'expectedQuery' => [
					'sql' => 'DELETE FROM users WHERE id = :id AND username = :username',
					'params' => [
						':id' => 1,
						':username' => 'testuser'
					]
				],
				'expectedExceptionClass' => null,
				'expectedExceptionMessage' => null,
				'customSetup' => function ($mapper) {
					$mapper->SetPropertyDbMap([
						'Id' => new Property('id', 'integer'),
						'Username' => new Property('username', 'string')
					]);
				}
			],
		];
	}

	// Testing for public interface:
	public function testGetNew() {
		// Retrieve a new model using the GetNew method
		$newModel = $this->mapper->GetNew();

		// Ensure the returned model is not the same instance as the original
		$this->assertNotSame($newModel, $this->mapper->GetNew(), 'GetNew should return a new instance of the model.');

		// Ensure the returned model is of the correct type
		$this->assertInstanceOf(
			ConcreteModel::class,
			$newModel,
			'GetNew should return an instance of the expected model class.'
		);
	}

	public function testGetOneById() {
		// Mock the return value for executeQuery to simulate a successful database fetch
		$this->mapper->SetExecuteReturn([
			[
				'Id' => 1,
				'Username' => 'testuser',
				'Password' => 'password123'
			]
		]);

		// Call GetOneById with a valid ID
		$model = $this->mapper->GetOneById(1);

		// Ensure a model is returned
		$this->assertInstanceOf(
			ConcreteModel::class, $model,
			'GetOneById should return a model when a matching ID is found.'
		);

		// Ensure the returned model has the correct properties
		$this->assertEquals(1, $model->Id, 'The returned model should have the correct ID.');
		$this->assertEquals('testuser', $model->Username, 'The returned model should have the correct Username.');
		$this->assertEquals('password123', $model->Password, 'The returned model should have the correct Password.');

		// Mock the return value for executeQuery to simulate no matching record
		$this->mapper->SetExecuteReturn([]);

		// Call GetOneById with a non-existent ID
		$model = $this->mapper->GetOneById(999);

		// Ensure null is returned
		$this->assertNull($model, 'GetOneById should return null when no matching ID is found.');
	}

	public function testGetOne() {
		// Set up the propertyDbMap with the expected mappings
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Mock the return value for executeQuery to simulate a successful database fetch
		$this->mapper->SetExecuteReturn([
			[
				'Id' => 1,
				'Username' => 'testuser',
				'Password' => 'password123'
			]
		]);

		// Call GetOne with valid filters
		$model = $this->mapper->GetOne([
			new Filter('Username', '=', 'testuser')
		]);

		// Ensure a model is returned
		$this->assertInstanceOf(
			ConcreteModel::class,
			$model,
			'GetOne should return a model when a matching record is found.'
		);

		// Ensure the returned model has the correct properties
		$this->assertEquals(1, $model->Id, 'The returned model should have the correct ID.');
		$this->assertEquals('testuser', $model->Username, 'The returned model should have the correct Username.');
		$this->assertEquals('password123', $model->Password, 'The returned model should have the correct Password.');

		// Mock the return value for executeQuery to simulate no matching record
		$this->mapper->SetExecuteReturn([]);

		// Call GetOne with filters that don't match anything
		$model = $this->mapper->GetOne([
			new Filter('Username', '=', 'nonexistentuser')
		]);

		// Ensure null is returned
		$this->assertNull($model, 'GetOne should return null when no matching record is found.');
	}

	public function testSave() {
		// Set up the propertyDbMap with the expected mappings
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Mock the writer to simulate lastInsertId for performCreate
		$this->writer
			->expects($this->once())
			->method('lastInsertId')
			->willReturn('1');

		// Mock executeQuery for performCreate
		$this->mapper->SetExecuteReturn(null);

		// Test saving a new model
		$newModel           = new ConcreteModel();
		$newModel->Id       = null; // Indicating it's a new model
		$newModel->Username = 'newuser';
		$newModel->Password = 'newpassword';

		$this->mapper->Save($newModel);

		// Verify that the ID is now set after "insert"
		$this->assertEquals(1, $newModel->Id, 'The ID should be set on the model after creation.');

		// Reset the writer for the update case (performUpdate)
		$this->writer
			->expects($this->never()) // lastInsertId should not be called
			->method('lastInsertId');

		// Test updating an existing model
		$existingModel           = new ConcreteModel();
		$existingModel->Id       = 1; // Indicating it's an existing model
		$existingModel->Username = 'updateduser';
		$existingModel->Password = 'updatedpassword';

		$this->mapper->Save($existingModel);

		// No changes to the ID
		$this->assertEquals(1, $existingModel->Id, 'The ID should remain unchanged on the model after update.');
	}

	public function testSaveValidation() {
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		$this->writer
			->expects($this->once())
			->method('lastInsertId')
			->willReturn('1');

		$this->mapper->SetExecuteReturn(null);

		// Test saving a valid model
		$validModel           = new ConcreteModel();
		$validModel->Id       = null; // Simulate a new model
		$validModel->Username = 'newuser';
		$validModel->Password = 'newpassword';
		$validModel->Valid    = true; // Ensure it passes validation

		$this->mapper->Save($validModel);

		$this->assertEquals(1, $validModel->Id, 'The ID should be set on the model after saving a valid model.');

		// Test saving an invalid model
		$invalidModel           = new ConcreteModel();
		$invalidModel->Id       = 1; // Simulate an existing model
		$invalidModel->Username = 'updateduser'; // Modify the model to ensure GetModifiedProperties is not empty
		$invalidModel->Valid    = false; // Ensure it fails validation

		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidModelException::class);
		$this->expectExceptionMessage('Model failed validation check.');

		$this->mapper->Save($invalidModel);
	}

	public function testCount() {
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Case 1: Count with no filters
		$this->mapper->SetExecuteReturn([
			['count' => 5] // Simulate the database returning 5 rows
		]);
		$count = $this->mapper->Count();
		$this->assertEquals(5, $count, 'Count should return the correct number of rows when no filters are applied.');

		// Case 2: Count with filters
		$this->mapper->SetExecuteReturn([
			['count' => 2] // Simulate the database returning 2 rows
		]);
		$count = $this->mapper->Count([
			new Filter('Username', '=', 'testuser')
		]);
		$this->assertEquals(2, $count, 'Count should return the correct number of rows when filters are applied.');

		// Case 3: No result returned
		$this->mapper->SetExecuteReturn([]);
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class);
		$this->expectExceptionMessage('Count query did not return a count.');
		$this->mapper->Count();
	}

	public function testDelete() {
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Mock executeQuery to simulate the delete operation
		$this->mapper->SetExecuteReturn(null);

		// Case 1: Delete a valid model
		$model     = new ConcreteModel();
		$model->Id = 1; // Ensure the model has a valid ID

		$this->mapper->Delete($model);

		// Assert the model reference is nullified
		$this->assertNull($model, 'Delete should nullify the model reference after successful deletion.');

		// Case 2: Attempt to delete a model with no ID (should throw an exception)
		$invalidModel     = new ConcreteModel();
		$invalidModel->Id = null; // Model has no valid ID

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot delete a model without a valid ID.');

		$this->mapper->Delete($invalidModel);
	}

	public function testDeleteById() {
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Mock executeQuery to simulate the delete operation
		$this->mapper->SetExecuteReturn(null);

		// Case 1: Valid ID
		$this->mapper->DeleteById(1);

		// Assert no exceptions were thrown (implicit if no error occurs)
		$this->assertTrue(true, 'DeleteById should not throw an exception for a valid ID.');

		// Case 2: Invalid ID (e.g., null or empty)
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot delete a record without a valid ID.');

		$this->mapper->DeleteById(null);
	}

	public function testDeleteOneWhere() {
		$this->mapper->SetPropertyDbMap([
			'Id' => new Property('id', 'integer'),
			'Username' => new Property('username', 'string'),
			'Password' => new Property('password', 'string')
		]);

		// Mock executeQuery to simulate the delete operation
		$this->mapper->SetExecuteReturn(null);

		// Case 1: Valid filters
		$this->mapper->DeleteOneWhere(['Username' => 'testuser']);
		$this->assertTrue(true, 'DeleteOneWhere should not throw an exception for valid filters.');

		// Case 2: Invalid filter property
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class);
		$this->expectExceptionMessage("Invalid property 'InvalidProperty' in filters.");
		$this->mapper->DeleteOneWhere(['InvalidProperty' => 'value']);

		// Case 3: Empty filters
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('No conditions provided for delete.');
		$this->mapper->DeleteOneWhere([]);
	}

	public function testExecuteQuery() {
		// Create the specialized subclass for this test
		$mapper = new ConcreteGenericSqlForExecuteTest(
			$this->mapperFactory,
			$this->model,
			$this->reader,
			$this->writer
		);

		/** @var \PDO|MockObject $pdo */
		$pdo = $this->createMock(\PDO::class);
		/** @var \PDOStatement|MockObject $stmt */
		$stmt = $this->createMock(\PDOStatement::class);

		$pdo->expects($this->once())
			->method('prepare')
			->with('SELECT * FROM users WHERE id = :id')
			->willReturn($stmt);

		$stmt->expects($this->once())
			->method('execute')
			->with([':id' => 1])
			->willReturn(true);

		$stmt->expects($this->once())
			->method('fetchAll')
			->with(\PDO::FETCH_ASSOC)
			->willReturn([['id' => 1, 'username' => 'testuser']]);

		$result = $mapper->PublicExecuteQuery($pdo, 'SELECT * FROM users WHERE id = :id', [':id' => 1], true);

		$this->assertEquals([['id' => 1, 'username' => 'testuser']], $result);
	}

	public function testExecuteQueryWithExecuteException() {
		/** @var \PDO|MockObject $pdo */
		$pdo = $this->createMock(\PDO::class);
		/** @var \PDOStatement|MockObject $stmt */
		$stmt = $this->createMock(\PDOStatement::class);

		$pdo->expects($this->once())
			->method('prepare')
			->willReturn($stmt);

		$stmt->expects($this->once())
			->method('execute')
			->willThrowException(new \PDOException('Execution error', 5678));

		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\MapperException::class);
		$this->expectExceptionMessage('Error executing query: Execution error');
		$this->expectExceptionCode(5678);

		$mapper = new ConcreteGenericSqlForExecuteTest(
			$this->mapperFactory,
			$this->model,
			$this->reader,
			$this->writer
		);

		$mapper->PublicExecuteQuery(
			$pdo,
			'SELECT * FROM users WHERE id = :id',
			[':id' => 1],
			true
		);
	}
}

// @codingStandardsIgnoreStart
class ConcreteModel extends Model {
	public function __construct() {
		parent::__construct([
			'Id' => 1,
			'Username' => 'testuser',
			'Password' => 'testpassword',
			'Valid' => true // Default to valid
		]);
	}
	protected function validateValid($value): bool {
		return $value === true;
	}
}

class ConcreteGenericSql extends GenericSql {
	protected string $idProperty = 'Id';
	protected array $propertyDbMap = [
		'Id' => 'id',
		'Username' => 'username',
		'Password' => 'password',
		'CreatedDate' => ['column' => 'created_date', 'type' => 'datetime'],
	];

	protected string $dbTable = 'users';

	private mixed $executeReturn = null;

	// Allow changing the propertyDbMap for testing
	public function SetPropertyDbMap(array $dbProperties): void {
		$propertyDbMap = [];
		foreach ($dbProperties as $propertyName => $property) {
			if ($property instanceof Property) {
				$propertyDbMap[$propertyName] = $property;
			} else {
				$propertyDbMap[$propertyName] = new Property($propertyName, 'string');
			}
		}
		$this->propertyDbMap = $propertyDbMap;
	}
	// Allow changing the joins for testing
	public function SetJoins(array $joins): void {
		foreach ($joins as $join) {
			if (!$join instanceof Join) {
				throw new \InvalidArgumentException('Joins must be an array of Join objects.');
			}
		}
		$this->joins = $joins;
	}

	// Expose protected methods for testing
	public function PublicHasAggregatesInSelect(): bool {
		return $this->hasAggregatesInSelect();
	}

	// Expose query methods for testing
	public function PublicGetSelect(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array {
		return $this->getSelectQuery($filters, $sort, $page, $pageSize);
	}
	public function PublicGetCount(array $filters = []): array {
		return $this->getCountQuery($filters);
	}
	public function PublicGetInsert(array $data): array {
		return $this->getInsertQuery($data);
	}
	public function PublicGetUpdate(array $data, array $conditions): array {
		return $this->getUpdateQuery($data, $conditions);
	}
	public function PublicGetDelete(array $conditions): array {
		return $this->getDeleteQuery($conditions);
	}

	// Mock executeQuery for testing
	public function SetExecuteReturn($value): void {
		$this->executeReturn = $value;
	}
	protected function executeQuery(\PDO $dbConnection, string $sql, array $params, bool $fetch = false): ?array {
		return $this->executeReturn;
	}
}
class ConcreteGenericSqlForExecuteTest extends GenericSql {
	protected string $idProperty = 'Id';
	protected array $propertyDbMap = [
		'Id' => 'id',
		'Username' => 'username',
		'Password' => 'password',
		'CreatedDate' => ['column' => 'created_date', 'type' => 'datetime'],
	];
	protected string $dbTable = 'users';
	public function PublicExecuteQuery(\PDO $dbConnection, string $sql, array $params, bool $fetch): ?array {
		return $this->executeQuery($dbConnection, $sql, $params, $fetch);
	}
}
// @codingStandardsIgnoreEnd
