<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {
	public function testConstructorInitializesProperties(): void {
		$model = new TestModel(['Property' => 'validValue']);
		$this->assertEquals('validValue', $model->GetProperties()['Property']);
		$this->assertEquals(null, $model->GetProperties()['OptionalProperty']);
	}

	public function testGetAndSetProperties(): void {
		$model                   = new TestModel();
		$model->OptionalProperty = 'optionalValue';
		$this->assertEquals('optionalValue', $model->OptionalProperty);
	}

	public function testModifiedProperties(): void {
		$model                   = new TestModel();
		$model->OptionalProperty = 'newValue';
		$this->assertEquals(['OptionalProperty' => 'newValue'], $model->GetModifiedProperties());
	}

	public function testArrayAccess(): void {
		$model                     = new TestModel();
		$model['OptionalProperty'] = 'arrayValue';
		$this->assertEquals('arrayValue', $model['OptionalProperty']);
		unset($model['OptionalProperty']);
		$this->assertFalse(isset($model['OptionalProperty']));
	}

	public function testIteration(): void {
		$model      = new TestModel(['Property' => 'validValue', 'OptionalProperty' => 'optionalValue']);
		$properties = [];
		foreach ($model as $key => $value) {
			$properties[$key] = $value;
		}
		$this->assertEquals($model->GetProperties(), $properties);
	}

	public function testValidationPasses(): void {
		$model = new TestModel(['Property' => 'validValue']);
		$this->assertTrue($model->IsValid());
		$this->assertEmpty($model->GetValidationErrors());
	}

	public function testValidationFails(): void {
		$model = new TestModel(['Property' => 'invalidValue']);
		$this->assertFalse($model->IsValid());
		$this->assertArrayHasKey('Property', $model->GetValidationErrors());
		$this->assertEquals('Invalid value for Property', $model->GetValidationErrors()['Property']);
	}

	public function testInitializeProperties(): void {
		$model = new TestModel();
		$model->InitializeProperties(['Property' => 'initializedValue']);
		$this->assertEquals('initializedValue', $model->Property);
	}

	public function testToArray(): void {
		$model = new TestModel();
		$model->InitializeProperties([
			'Property' => 'value',
			'OptionalProperty' => 'optionalValue'
		]);

		$expectedArray = [
			'Property' => 'value',
			'OptionalProperty' => 'optionalValue'
		];

		$this->assertEquals($expectedArray, $model->toArray());
	}

	public function testToString(): void {
		$model = new TestModel();
		$model->InitializeProperties([
			'Property' => 'value',
			'OptionalProperty' => 'optionalValue'
		]);

		$classAsString = get_class($model) . " object {\n";
		foreach ($model->GetProperties() as $key => $value) {
			$classAsString .= "  $key: {$value}\n";
		}
		$classAsString .= "}\n";

		$this->assertEquals($classAsString, (string) $model);
	}

	public function testIssetNonExistentProperty(): void {
		$model = new TestModel();

		// Assert that isset() returns false for a non-existent property
		$this->assertFalse(
			isset($model->NonExistentProperty), 'Expected isset() to return false for a non-existent property.'
		);
	}

	public function testUnsetProperty(): void {
		$model = new TestModel();

		// Set a property value
		$model->Property = 'value';
		$this->assertEquals('value', $model->Property);

		// Unset the property
		unset($model->Property);

		// Verify that the property is reset to null
		$this->assertNull($model->Property, 'Expected the property to be set to null after unset.');

		// Verify that the property is no longer in modified properties
		$this->assertArrayNotHasKey('Property', $model->GetModifiedProperties());
	}

	public function testJsonSerialize() {
		// Create an instance of TestModel
		$testModel = new TestModel();

		// Set properties
		$testModel->Property         = 'validValue';
		$testModel->OptionalProperty = 'Optional Value';

		// Expected array for jsonSerialize
		$expectedJson = [
			'Property' => 'validValue',
			'OptionalProperty' => 'Optional Value'
		];

		// Assert that jsonSerialize produces the correct array
		$this->assertSame($expectedJson, $testModel->jsonSerialize());

		// Assert that json_encode works as expected with the model
		$this->assertSame(json_encode($expectedJson), json_encode($testModel));
	}
}
/**
 * Class TestModel used to test the abstract Model class.
 * @package Fluxoft\Rebar
 */
// @codingStandardsIgnoreStart
class TestModel extends Model {
	protected static array $defaultProperties = [
		'Property'        => null,  // Single property for validation tests
		'OptionalProperty' => null // Additional property to demonstrate custom getters/setters
	];

	/**
	 * Validate the "Property" value.
	 * Accepts only the string "validValue" as valid.
	 */
	protected function validateProperty($value): bool|string {
		return $value === 'validValue' ? true : 'Invalid value for Property';
	}

	protected function getOptionalProperty(): ?string {
		return $this->properties['OptionalProperty'];
	}

	protected function setOptionalProperty($value): void {
		$this->properties['OptionalProperty'] = $value;
	}
}
// @codingStandardsIgnoreEnd
