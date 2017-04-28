<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

/**
 * Class ModelTest
 * @package Fluxoft\Rebar
 */
class ModelTest extends TestCase {
	protected function setup() {}

	protected function teardown() {}

	/**
	 * @dataProvider createProvider
	 * @param array $constructProperties
	 * @param array $setProperties
	 */
	public function testCreate(array $constructProperties, array $setProperties) {
		// Merge the properties to be passed to the constructor with the default set on
		// the concrete model as the default properties for the object.
		$defaultProperties = array_merge(
			['defaultKey' => 'defaultValue', 'arrayAccessKey' => null],
			$constructProperties
		);

		$testModel = new ConcreteModel($constructProperties);

		// the GetProperties method should contain the exact same list of properties we
		// start with as the default on the model, plus the constructor properties.
		$this->assertEquals($testModel->GetProperties(), $defaultProperties);

		// nothing has been modified, so GetModifiedProperties should return an empty array.
		$this->assertEquals($testModel->GetModifiedProperties(), []);

		// test toArray() method
		$this->assertEquals($testModel->toArray(), $defaultProperties);

		// test all properties are set to expected values
		foreach ($defaultProperties as $key => $value) {
			$this->assertEquals(($defaultProperties[$key] !== null), isset($testModel->$key));
			$this->assertEquals($defaultProperties[$key], $testModel->$key);
		}

		// test Iterator implementation
		$currentProperties = $testModel->GetProperties();
		foreach ($testModel as $item => $value) {
			$this->assertEquals($currentProperties[$item], $value);
		}

		// test ArrayAccess implementation
		$this->assertTrue(isset($testModel['defaultKey']));
		$this->assertEquals($testModel['defaultKey'], 'defaultValue');
		$testModel['arrayAccessKey'] = 'arrayAccessValue';
		$this->assertEquals($testModel['arrayAccessKey'], 'arrayAccessValue');
		unset($testModel['arrayAccessKey']);
		$this->assertFalse(isset($testModel['arrayAccessKey']));

		// validate model
		if (isset($constructProperties['InvalidProperty'])) {
			$this->assertFalse($testModel->IsValid());
			$validationErrors                    = [];
			$validationErrors['InvalidProperty'] = "Invalid value {$testModel['InvalidProperty']}";
			$this->assertEquals($validationErrors, $testModel->GetValidationErrors());
		} else {
			$this->assertTrue($testModel->IsValid());
		}

		// test __toString()
		$classAsString = get_class($testModel) . " object {\n";
		foreach ($testModel->GetProperties() as $key => $value) {
			$classAsString .= "  $key: {$testModel->$key}\n";
		}
		$classAsString .= "}\n";
		$this->assertEquals($classAsString, (string) $testModel);

		// try setting values
		foreach ($setProperties as $key => $value) {
			if ($key === 'NonExistentProperty') {
				$this->expectException('InvalidArgumentException');
			}
			$testModel->$key = $value;
		}
		// make sure only the modified values are in GetModifiedProperties()
		$modified = [];
		foreach ($testModel->GetProperties() as $key => $value) {
			if (isset($setProperties[$key]) && ($setProperties !== $value)) {
				$modified[$key] = $value;
			}
		}
		$this->assertEquals($modified, $testModel->GetModifiedProperties());

		// test unsetting all values
		foreach ($defaultProperties as $key => $value) {
			unset($testModel->$key);
			$this->assertFalse(isset($testModel->$key));
		}
	}
	public function createProvider() {
		return [
			'emptyDefault' => [
				'constructProperties' => [],
				'setProperties' => []
			],
			'invalidProperty' => [
				'constructProperties' => [
					'InvalidProperty' => 'should fail validation'
				],
				'setProperties' => []
			],
			'validProperty' => [
				'constructProperties' => [
					'ValidProperty' => 'should pass validation'
				],
				'setProperties' => []
			],
			'functionProperties' => [
				'constructProperties' => [
					'FunctionProperty' => null
				],
				'setProperties' => [
					'FunctionProperty' => 'new'
				]
			],
			'nonExistentProperty' => [
				'constructProperties' => [],
				'setProperties' => [
					'NonExistentProperty' => 'new'
				]
			]
		];
	}
	public function testGetNonExistentProperty() {
		$testModel = new ConcreteModel();

		// get, isset, unset NonExistentProperty
		$this->expectException('InvalidArgumentException');
		$null = $testModel->NonExistentProperty;
		unset($null);
	}
	public function testIssetNonExistentProperty() {
		$testModel = new ConcreteModel();

		$this->expectException('InvalidArgumentException');
		$null = isset($testModel->NonExistentProperty);
		unset($null);
	}
	public function testUnsetNonExistentProperty() {
		$testModel = new ConcreteModel();

		$this->expectException('InvalidArgumentException');
		unset($testModel->NonExistentProperty);
	}
}

/**
 * Class ConcreteModel used to test the abstract Model class.
 * @package Fluxoft\Rebar
 * @property string FunctionProperty
 */
// @codingStandardsIgnoreStart
class ConcreteModel extends Model {
	// @codingStandardsIgnoreEnd
	protected $properties = ['defaultKey' => 'defaultValue', 'arrayAccessKey' => null];

	protected function validateInvalidProperty($value) {
		return "Invalid value $value";
	}
	protected function validateValidProperty($value) {
		return $value === $value;
	}
	protected function setFunctionProperty($value) {
		$this->properties['FunctionProperty']    = $value;
		$this->modProperties['FunctionProperty'] = $value;
	}
	protected function getFunctionProperty() {
		return $this->properties['FunctionProperty'];
	}
}
