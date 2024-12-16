<?php

namespace Fluxoft\Rebar\Data\Db;

use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase {
	/**
	 * Test that a property with an aggregate function is identified correctly.
	 */
	public function testAggregateProperty() {
		$property = new Property('COUNT(id)', 'integer');

		$this->assertTrue(
			$property->IsAggregate,
			'Property with an aggregate function should return true for IsAggregate.'
		);
		$this->assertFalse(
			$property->IsWriteable,
			'Property with an aggregate function should return false for IsWriteable.'
		);

		// Access again to ensure caching logic is covered
		$this->assertFalse(
			$property->IsWriteable,
			'Second access to IsWriteable should still return false.'
		);
		$this->assertTrue(
			$property->IsAggregate,
			'Second access to IsAggregate should still return true.'
		);
	}

	/**
	 * Test that a foreign table column is identified correctly.
	 */
	public function testForeignTableProperty() {
		$property = new Property('table.column', 'string');

		$this->assertFalse(
			$property->IsAggregate,
			'Foreign table column should return false for IsAggregate.'
		);
		$this->assertFalse(
			$property->IsWriteable,
			'Foreign table column should return false for IsWriteable.'
		);
	}

	/**
	 * Test that a regular column is identified correctly.
	 */
	public function testRegularProperty() {
		$property = new Property('column', 'string');

		$this->assertFalse(
			$property->IsAggregate,
			'Regular property should return false for IsAggregate.'
		);
		$this->assertTrue(
			$property->IsWriteable,
			'Regular property should return true for IsWriteable.'
		);
	}

	/**
	 * Test that the type validation works correctly.
	 */
	public function testInvalidTypeThrowsException() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'Type must be one of: string, integer, float, boolean, datetime, date, time, text, binary.'
		);

		new Property('column', 'invalidtype');
	}
}
