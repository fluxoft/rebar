<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use Fluxoft\Rebar\Data\Db\Property;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\TestCase;

class GenericSqlTest extends TestCase {
	protected ConcreteGenericSql $mapper;

	protected function setUp(): void {
		/** @var MapperFactory $mapperFactory */
		$mapperFactory = $this->createMock(MapperFactory::class);
		/** @var Model $model */
		$model = $this->createMock(Model::class);
		/** @var \PDO $reader */
		$reader = $this->createMock(\PDO::class); // Correct reference to PDO
		/** @var \PDO $writer */
		$writer = $this->createMock(\PDO::class); // Correct reference to PDO

		// Directly instantiate the ConcreteGenericSql class
		$this->mapper = new ConcreteGenericSql($mapperFactory, $model, $reader, $writer);
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

	public function testGetSelectGeneratesCorrectSQL() {
		$select = $this->mapper->getSelect([], [], 1, 10);
			
		$this->assertStringContainsString('SELECT * FROM users', $select['sql']);
	}

	public function testGetOneByIdReturnsModel() {
		// Mocking database behavior directly
		$statement = $this->createMock(\PDOStatement::class);
		$statement->method('fetch')->willReturn([
			'Id' => 1,
			'Username' => 'testuser',
			'Password' => 'testpassword'
		]);

		// Access the protected $reader property using reflection
		$reflection     = new \ReflectionClass($this->mapper);
		$readerProperty = $reflection->getProperty('reader');
		$readerProperty->setAccessible(true);
		$reader = $readerProperty->getValue($this->mapper);

		$reader->method('prepare')->willReturn($statement);

		// Replace the model with ConcreteModel in the mapper
		$modelReflection = new \ReflectionClass($this->mapper);
		$modelProperty   = $modelReflection->getProperty('model');
		$modelProperty->setAccessible(true);
		$modelProperty->setValue($this->mapper, new ConcreteModel());

		$model = $this->mapper->GetOneById(1);

		// Ensure the model is properly initialized
		$this->assertInstanceOf(ConcreteModel::class, $model);
		$this->assertArrayHasKey('Id', $model->GetProperties());
		$this->assertArrayHasKey('Username', $model->GetProperties());
		$this->assertArrayHasKey('Password', $model->GetProperties());
		$this->assertEquals(1, $model->GetProperties()['Id']);
		$this->assertEquals('testuser', $model->GetProperties()['Username']);
		$this->assertEquals('testpassword', $model->GetProperties()['Password']);
	}

	public function testDeleteRemovesModel() {
		$model = new ConcreteModel();
		$model->InitializeProperties([
			'Id' => 1,
			'Username' => 'testuser',
			'Password' => 'testpassword'
		]);
		
		// Mock the DELETE query
		$statement = $this->createMock(\PDOStatement::class);
		$statement->method('execute')->willReturn(true);
		
		// Access the protected $writer property using reflection
		$reflection     = new \ReflectionClass($this->mapper);
		$writerProperty = $reflection->getProperty('writer');
		$writerProperty->setAccessible(true);
		
		// Replace the writer with a mock
		$writer = $this->createMock(\PDO::class);
		$writer->method('prepare')->willReturn($statement);
		$writerProperty->setValue($this->mapper, $writer);
		
		// Call the Delete method
		$this->mapper->Delete($model);
		
		// Assert that the model was "deleted"
		$this->assertNull($model);
	}
}

// @codingStandardsIgnoreStart
class ConcreteModel extends Model {
	public function __construct() {
		parent::__construct(
		[
		'Id' => 1,
		'Username' => 'testuser',
		'Password' => 'testpassword'
		]
		);
	}
}

class ConcreteGenericSql extends GenericSql {
	protected string $idProperty = 'Id';
	protected array $propertyDbMap = [
		'Id' => 'id',
		'Username' => 'username',
		'Password' => 'password'
	];
	
	protected string $dbTable = 'users'; // Initialize dbTable

	public function getSelect(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array {
		// Mock implementation for testing
		return [
		'sql' => 'SELECT * FROM ' . $this->quoteIdentifier($this->dbTable),
		'params' => []
		];
	}
}
// @codingStandardsIgnoreEnd
