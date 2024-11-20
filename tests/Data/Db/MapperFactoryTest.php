<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\Data\Db\Exceptions\MapperFactoryException;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Mappers\ConcreteModel;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase {
	/** @var \PDO|MockObject */
	private $connectionObserver;
	/** @var ConcreteModel|MockObject */
	private $concreteModelObserver;

	protected function setup():void {
		$this->connectionObserver    = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$this->concreteModelObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\ConcreteModel')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->concreteModelObserver);
		unset($this->connectionObserver);
	}

	public function testModelNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = ConcreteModelForMapperFactoryTestMapper::class;
		$modelClass    = '\Fluxoft\Rebar\Data\Db\NonExistent';

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf(
			'The model could not be found: "%s"',
			$modelClass
		));

		$mapperFactory->Build($mapperClass, ['modelClass' => $modelClass]);
	}

	public function testModelFromMapperNameNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = NoModelMapper::class;
		$modelClass    = 'Fluxoft\Rebar\Data\Db\NoModel';

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf(
			'The model could not be found: "%s"',
			$modelClass
		));

		$mapperFactory->Build($mapperClass);
	}

	public function testModelFromClassNotModel() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = ConcreteModelForMapperFactoryTestMapper::class;
		$modelClass    = '\Fluxoft\Rebar\Data\Db\BadConcreteModel';

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf(
			'Model %s is not an instance of Model',
			substr($modelClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['modelClass' => $modelClass]);
	}

	public function testModelGivenNotModel() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = ConcreteModelForMapperFactoryTestMapper::class;
		$model         = new BadConcreteModel();
		$modelClass    = '\Fluxoft\Rebar\Data\Db\BadConcreteModel';

		$model = new $modelClass();

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf(
			'Model %s is not an instance of Model',
			substr($modelClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['model' => $model]);
	}

	public function testNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = 'NonExistentClass';

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf('The mapper could not be found: "%s"', $mapperClass));

		$mapperFactory->Build($mapperClass, ['model' => $this->concreteModelObserver]);
	}

	public function testNotMapper() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Data\Db\BadConcreteModelMapper';

		$this->expectException(MapperFactoryException::class);
		$this->expectExceptionMessage(sprintf(
			'Requested class %s does not extend MapperInterface',
			substr($mapperClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['model' => $this->concreteModelObserver]);
	}

	public function testBuild() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Data\Db\ConcreteModelForMapperFactoryTestMapper';

		$this->assertInstanceOf(
			'\Fluxoft\Rebar\Data\Db\Mappers\MapperInterface',
			$mapperFactory->Build($mapperClass)
		);
	}
}

// @codingStandardsIgnoreStart
class BadConcreteModelMapper {}
class BadConcreteModel {}
class ConcreteMapperFactory extends MapperFactory {}
class ConcreteModelForMapperFactoryTestMapper extends GenericSql {
	protected string $idProperty = 'Foo';
	protected string $dbTable       = 'foo';
	protected array $propertyDbMap = ['Foo' => 'foo'];
}
class ConcreteModelForMapperFactoryTest extends Model {}
class NoModelMapper extends GenericSql { // No corresponding model
	protected string $idProperty = 'Foo';
	protected string $dbTable       = 'foo';
	protected array $propertyDbMap = ['Foo' => 'foo'];
}
// @codingStandardsIgnoreEnd
