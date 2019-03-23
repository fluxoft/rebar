<?php

namespace Fluxoft\Rebar\Db;

use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase {
	private $connectionObserver;
	private $concreteModelObserver;
	protected function setup() {
		$this->connectionObserver    = $this->getMockBuilder('\Doctrine\DBAL\Connection')
			->disableOriginalConstructor()
			->getMock();
		$this->concreteModelObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\ConcreteModel')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
		unset($this->concreteModelObserver);
		unset($this->connectionObserver);
	}

	public function testModelNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\NonExistentMapper';
		$modelClass    = '\Fluxoft\Rebar\Db\NonExistent';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf(
			'The model could not be found: "%s"',
			$modelClass
		));

		$mapperFactory->Build($mapperClass);
	}

	public function testModelFromMapperNameNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\BadConcreteModel';
		$modelClass    = 'Fluxoft\Rebar\Db\BadConcreteModel';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf(
			'Model %s is not an instance of Model',
			$modelClass
		));

		$mapperFactory->Build($mapperClass);
	}

	public function testModelFromClassNotModel() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\BadConcreteModelMapper';
		$modelClass    = '\Fluxoft\Rebar\Db\BadConcreteModel';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf(
			'Model %s is not an instance of Model',
			substr($modelClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['modelClass' => $modelClass]);
	}

	public function testModelGivenNotModel() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\ConcreteModelMapper';
		$model         = new BadConcreteModel();
		$modelClass    = '\Fluxoft\Rebar\Db\BadConcreteModel';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf(
			'Model %s is not an instance of Model',
			substr($modelClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['model' => $model]);
	}

	public function testNotFound() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = 'NonExistentClass';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf('The mapper could not be found: "%s"', $mapperClass));

		$mapperFactory->Build($mapperClass, ['model' => $this->concreteModelObserver]);
	}

	public function testNotMapper() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\BadConcreteModelMapper';

		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\MapperFactoryException');
		$this->expectExceptionMessage(sprintf(
			'Requested class %s is not an instance of Mapper',
			substr($mapperClass, 1)
		));

		$mapperFactory->Build($mapperClass, ['model' => $this->concreteModelObserver]);
	}

	public function testBuild() {
		$mapperFactory = new ConcreteMapperFactory($this->connectionObserver);
		$mapperClass   = '\Fluxoft\Rebar\Db\ConcreteModelForMapperFactoryTestMapper';

		$this->assertInstanceOf(
			'\Fluxoft\Rebar\Db\Mapper',
			$mapperFactory->Build($mapperClass)
		);
	}
}

// @codingStandardsIgnoreStart
class BadConcreteModelMapper {}
class BadConcreteModel {}
class ConcreteMapperFactory extends MapperFactory {}
class ConcreteModelForMapperFactoryTestMapper extends Mapper {}
class ConcreteModelForMapperFactoryTest extends Model {
	protected $idProperty    = 'Foo';
	protected $dbTable       = 'foo';
	protected $propertyDbMap = ['Foo' => 'foo'];
}
// @codingStandardsIgnoreEnd
