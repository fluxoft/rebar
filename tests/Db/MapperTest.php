<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase {
	/** @var MapperFactory|MockObject */
	private $mapperFactoryObserver;
	/** @var ConcreteModelForMapperTest|MockObject */
	private $concreteModelObserver;
	/** @var Connection|MockObject */
	private $connectionObserver;
	protected function setup():void {
		$this->mapperFactoryObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\MapperFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->concreteModelObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\ConcreteModelForMapperTest')
			->disableOriginalConstructor()
			->getMock();
		$this->connectionObserver    = $this->getMockBuilder('\Doctrine\DBAL\Connection')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->connectionObserver);
		unset($this->modelObserver);
		unset($this->mapperFactoryObserver);
	}

	public function testGetNew() {
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$this->concreteModelObserver,
			$this->connectionObserver
		);

		$model = $mapper->GetNew();

		$this->assertEquals($this->concreteModelObserver, $model);
	}

	/**
	 * @param int   $requestId
	 * @param array $results
	 * @dataProvider providerGetOneById
	 */
	public function testGetOneById(
		$requestId,
		$results
	) {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$this->connectionObserver
			->expects($this->once())
			->method('fetchAllAssociative')
			->will($this->returnValue($results));

		$model = $mapper->GetOneById($requestId);

		if (empty($results)) {
			$this->assertNull($model);
		} else {
			$result = $results[0];
			foreach ($result as $key => $value) {
				$this->assertEquals($value, $model->$key);
			}
		}
	}
	public function providerGetOneById() {
		return [
			[
				'requestId' => 1,
				'results'   => []
			],
			[
				'requestId' => 66,
				'results'   => [
					[
						'Id'   => 66,
						'Name' => 'foo'
					]
				]
			]
		];
	}

	/**
	 * @param array $filter
	 * @param array $results
	 * @dataProvider providerGetOneWhere
	 */
	public function testGetOneWhere(
		$filter,
		$results
	) {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$this->connectionObserver
			->expects($this->once())
			->method('fetchAllAssociative')
			->will($this->returnValue($results));

		$model = $mapper->GetOneWhere($filter);

		if (empty($results)) {
			$this->assertNull($model);
		} else {
			$result = $results[0];
			foreach ($result as $key => $value) {
				$this->assertEquals($value, $model->$key);
			}
		}
	}
	public function providerGetOneWhere() {
		return [
			[
				'filter' => [],
				'results'   => []
			],
			[
				'filter' => [
					'Name' => 'foo'
				],
				'results'   => [
					[
						'Id'   => 66,
						'Name' => 'foo'
					]
				]
			]
		];
	}

	/**
	 * @param array $filter
	 * @param array $sort
	 * @param int   $page
	 * @param int   $pageSize
	 * @param array $results
	 * @dataProvider providerGetSetWhere
	 */
	public function testGetSetWhere(
		$filter,
		$sort,
		$page,
		$pageSize,
		$results
	) {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$this->connectionObserver
			->expects($this->once())
			->method('fetchAllAssociative')
			->will($this->returnValue($results));

		$modelSet = $mapper->GetSetWhere($filter, $sort, $page, $pageSize);

		$this->assertEquals(count($modelSet), count($results));

		$count = count($results);

		for ($i = 0; $i < $count; $i++) {
			$model  = $modelSet[$i];
			$result = $results[$i];
			foreach ($result as $key => $value) {
				$this->assertEquals($value, $model->$key);
			}
		}
	}
	public function providerGetSetWhere() {
		return [
			[
				'filter' => [],
				'sort' => [],
				'page' => 1,
				'pageSize' => 1,
				'results' => [
					[
						'Id' => 1,
						'Name' => 'foo',
						'CalculatedField' => 'bar'
					]
				]
			],
			[
				'filter' => [],
				'sort' => [],
				'page' => 2,
				'pageSize' => 10,
				'results' => [
					[
						'Id' => 1,
						'Name' => 'foo',
						'CalculatedField' => 'bar'
					],
					[
						'Id' => 2,
						'Name' => 'foo2',
						'CalculatedField' => 'bar2'
					]
				]
			],
			[
				'filter' => ['Name' => 'foo'],
				'sort' => ['Name'],
				'page' => 1,
				'pageSize' => 1,
				'results' => [
					[
						'Id' => 1,
						'Name' => 'foo',
						'CalculatedField' => 'bar'
					]
				]
			],
			[
				'filter' => ['Name' => 'foo'],
				'sort' => ['Name Desc'],
				'page' => 1,
				'pageSize' => 1,
				'results' => [
					[
						'Id' => 1,
						'Name' => 'foo',
						'CalculatedField' => 'bar'
					]
				]
			],
			[
				'filter' => ['CalculatedField' => 'bar'],
				'sort' => ['Name'],
				'page' => 1,
				'pageSize' => 1,
				'results' => [
					[
						'Id' => 1,
						'Name' => 'foo',
						'CalculatedField' => 'bar'
					]
				]
			]
		];
	}

	public function testCountWhere() {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$statementObserver = $this->getMockBuilder('\Doctrine\DBAL\Statement')
			->disableOriginalConstructor()
			->getMock();
		$resultObserver = $this->getMockBuilder('\Doctrine\DBAL\Result')
			->disableOriginalConstructor()
			->getMock();

		$this->connectionObserver
			->expects($this->once())
			->method('prepare')
			->will($this->returnValue($statementObserver));
		$statementObserver
			->expects($this->once())
			->method('executeQuery')
			->will($this->returnValue($resultObserver));
		$resultObserver
			->expects($this->once())
			->method('fetchOne')
			->will($this->returnValue(1));

		$count = $mapper->CountWhere(['Name' => 'foo']);

		$this->assertEquals($count, 1);
	}

	/**
	 * @param $initialProperties
	 * @param $modProperties
	 * @param $finalProperties
	 * @dataProvider providerSave
	 */
	public function testSave(
		$initialProperties,
		$modProperties,
		$finalProperties
	) {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$model->InitializeProperties($initialProperties);

		foreach ($initialProperties as $key => $value) {
			$this->assertEquals($value, $model->$key);
		}

		foreach ($modProperties as $key => $value) {
			$model->$key = $value;
		}
		foreach ($modProperties as $key => $value) {
			$this->assertEquals($value, $model->$key);
		}

		if (isset($modProperties['Name']) && $modProperties['Name'] === 'fail') {
			$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidModelException');
		}

		$mapper->Save($model);

		foreach ($finalProperties as $key => $value) {
			$this->assertEquals($value, $model->$key);
		}
	}
	public function providerSave() {
		return [
			'invalidCreate' => [
				'initialProperties' => [
					'Id' => 0,
					'Name' => 'startName',
					'CalculatedField' => 'bar'
				],
				'modProperties' => [
					'Name' => 'fail'
				],
				'finalProperties' => [
					'Id' => 0,
					'Name' => 'fail',
					'CalculatedField' => 'bar'
				]
			],
			'invalidUpdate' => [
				'initialProperties' => [
					'Id' => 1,
					'Name' => 'startName',
					'CalculatedField' => 'bar'
				],
				'modProperties' => [
					'Name' => 'fail'
				],
				'finalProperties' => [
					'Id' => 1,
					'Name' => 'fail',
					'CalculatedField' => 'bar'
				]
			],
			'validCreate' => [
				'initialProperties' => [
					'Id' => 0,
					'Name' => 'startName',
					'CalculatedField' => 'bar'
				],
				'modProperties' => [
					'Name' => 'foo'
				],
				'finalProperties' => [
					'Id' => 0,
					'Name' => 'foo',
					'CalculatedField' => 'bar'
				]
			],
			'validUpdate' => [
				'initialProperties' => [
					'Id' => 1,
					'Name' => 'startName',
					'CalculatedField' => 'bar'
				],
				'modProperties' => [
					'Name' => 'foo'
				],
				'finalProperties' => [
					'Id' => 1,
					'Name' => 'foo',
					'CalculatedField' => 'bar'
				]
			]
		];
	}

	public function testDelete() {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$mapper->Delete($model);

		$this->assertNull($model);
	}

	public function testDeleteOneById() {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$this->connectionObserver
			->expects($this->once())
			->method('fetchAllAssociative')
			->will($this->returnValue([
				[
					'Id'   => 1,
					'Name' => 'foo'
				]
			]));

		$mapper->DeleteOneById(1);
	}

	public function testDeleteOneWhere() {
		$model  = new ConcreteModelForMapperTest();
		$mapper = new ConcreteMapper(
			$this->mapperFactoryObserver,
			$model,
			$this->connectionObserver
		);

		$this->connectionObserver
			->expects($this->once())
			->method('fetchAllAssociative')
			->will($this->returnValue([
				[
					'Id'   => 1,
					'Name' => 'foo'
				]
			]));

		$mapper->DeleteOneWhere([]);
	}
}

// @codingStandardsIgnoreStart
class ConcreteMapper extends Mapper {}
class ConcreteModelForMapperTest extends Model {
	protected $idProperty    = 'Id';
	protected $dbTable       = 'test';
	protected $properties    = ['Id' => 0, 'Name' => '', 'CalculatedField' => ''];
	protected $propertyDbMap = ['Id' => 'id', 'Name' => 'name'];

	protected function validateName() {
		return ($this->properties['Name'] === 'foo');
	}
}
// @codingStandardsIgnoreEnd
