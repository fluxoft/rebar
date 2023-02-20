<?php

namespace Fluxoft\Rebar\Db;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {
	public function testModelEmptyPropertyDbMap() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\ModelException');
		$this->expectExceptionMessage('You must specify the db column relationships in propertyDbMap');
		$model = new ConcreteModel(
			[],
			[]
		);
		unset($model);
	}
	public function testModelEmptyDbTable() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\ModelException');
		$this->expectExceptionMessage('You must specify the database table in dbTable');
		$model = new ConcreteModel(
			[],
			['foo' => 'bar'],
			''
		);
		unset($model);
	}
	public function testModelIdPropertyNotSet() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\ModelException');
		$this->expectExceptionMessage('The idProperty must be present in propertyDbMap');
		$model = new ConcreteModel(
			[],
			['foo' => 'bar'],
			'dbTable',
			'blah'
		);
		unset($model);
	}

	/**
	 * @param array  $initialProperties
	 * @param array  $initialPropertyDbMap
	 * @param string $initialDbTable
	 * @param string $initialIdProperty
	 * @param array  $properties
	 * @param array  $expectProperties
	 * @param array  $expectPropertyDbMap
	 * @param string $expectIdColumn
	 * @param int    $expectIdType
	 * @dataProvider modelProvider
	 */
	public function testModel(
		array  $initialProperties    = [],
		array  $initialPropertyDbMap = [],
		string $initialDbTable       = '',
		string $initialIdProperty    = '',
		array  $properties           = [],
		array  $expectProperties     = [],
		array  $expectPropertyDbMap  = [],
		string $expectIdColumn       = '',
		int    $expectIdType         = 0
	) {
		$model = new ConcreteModel(
			$initialProperties,
			$initialPropertyDbMap,
			$initialDbTable,
			$initialIdProperty,
			$properties
		);

		$this->assertEquals(
			$expectProperties,
			$model->GetProperties()
		);
		$this->assertEquals(
			$expectPropertyDbMap,
			$model->GetPropertyDbMap()
		);
		$this->assertEquals(
			$initialDbTable,
			$model->GetDbTable()
		);
		$this->assertEquals(
			$expectIdColumn,
			$model->GetIdColumn()
		);
		$this->assertEquals(
			$expectIdType,
			$model->GetIdType()
		);
		$this->assertEquals(
			$initialIdProperty,
			$model->GetIdProperty()
		);
		$this->assertEquals(
			$expectProperties[$initialIdProperty],
			$model->GetId()
		);
		$model->SetId(123);
		$expectProperties[$initialIdProperty] = 123;
		$this->assertEquals(
			$expectProperties,
			$model->GetProperties()
		);
		$this->assertEquals(
			123,
			$model->GetId()
		);
		$this->assertEquals(
			[],
			$model->GetModifiedProperties()
		);
	}
	public function modelProvider() {
		return [
			[
				'initialProperties' => [],
				'initialPropertyDbMap' => [
					'Id' => 'id',
					'PropertyOne' => 'property_one'
				],
				'initialDbTable' => 'db_table',
				'initialIdProperty' => 'Id',
				'properties' => [],
				'expectProperties' => [
					'Id' => 0,
					'PropertyOne' => null
				],
				'expectPropertyDbMap' => [
					'Id' => [
						'col' => 'id',
						'type' => \PDO::PARAM_STR
					],
					'PropertyOne' => [
						'col' => 'property_one',
						'type' => \PDO::PARAM_STR
					]
				],
				'expectIdColumn' => 'id',
				'expectIdType' => \PDO::PARAM_STR
			],
			[
				'initialProperties' => [],
				'initialPropertyDbMap' => [
					'Id' => [
						'col' => 'id',
						'type' => \PDO::PARAM_INT
					],
					'PropertyOne' => 'property_one'
				],
				'initialDbTable' => 'db_table',
				'initialIdProperty' => 'Id',
				'properties' => [
					'Id' => 1,
					'PropertyOne' => 'New'
				],
				'expectProperties' => [
					'Id' => 1,
					'PropertyOne' => 'New'
				],
				'expectPropertyDbMap' => [
					'Id' => [
						'col' => 'id',
						'type' => \PDO::PARAM_INT
					],
					'PropertyOne' => [
						'col' => 'property_one',
						'type' => \PDO::PARAM_STR
					]
				],
				'expectIdColumn' => 'id',
				'expectIdType' => \PDO::PARAM_INT
			]
		];
	}

	public function testInitializeProperties() {
		$model = new ConcreteModel(
			[],
			[
				'Id' => 'id',
				'PropertyOne' => 'property_one'
			],
			'db_table',
			'Id',
			[]
		);

		$unsetProperties = [
			'Id' => null,
			'PropertyOne' => null
		];
		$this->assertEquals($unsetProperties, $model->GetProperties());

		$initializeProperties = [
			'Id' => 66,
			'PropertyOne' => 'ValueOne'
		];
		$model->InitializeProperties($initializeProperties);
		$this->assertEquals($initializeProperties, $model->GetProperties());
	}
}

// @codingStandardsIgnoreStart
class ConcreteModel extends Model {
	// @codingStandardsIgnoreEnd

	public function __construct(
		array  $initialProperties    = null,
		array  $initialPropertyDbMap = null,
		string $initialDbTable       = null,
		string $initialIdProperty    = null,
		array  $properties           = []
	) {
		$this->properties    = $initialProperties;
		$this->propertyDbMap = $initialPropertyDbMap;
		$this->dbTable       = $initialDbTable;
		$this->idProperty    = $initialIdProperty;
		parent::__construct($properties);
	}

	public $properties    = [
		'Id' => null,
		'PropertyOne' => null
	];
	public $propertyDbMap = [
		'Id' => 'id',
		'PropertyOne' => 'property_one'
	];
	public $dbTable       = 'db_table';
	public $idProperty    = 'Id';
}
