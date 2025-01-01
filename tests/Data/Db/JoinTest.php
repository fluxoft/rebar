<?php
declare(strict_types=1);

namespace Fluxoft\Rebar\Data\Db;

use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase {
	public function testValidJoinConstruction() {
		$join = new Join('LEFT', 'users', 'users.id = orders.user_id', 'u');

		$this->assertEquals('LEFT', $join->Type, 'Join type should be set correctly.');
		$this->assertEquals('users', $join->Table, 'Table name should be set correctly.');
		$this->assertEquals('users.id = orders.user_id', $join->On, 'ON clause should be set correctly.');
		$this->assertEquals('u', $join->Alias, 'Alias should be set correctly.');
	}

	public function testValidJoinWithoutAlias() {
		$join = new Join('INNER', 'products', 'products.category_id = categories.id');

		$this->assertEquals('INNER', $join->Type, 'Join type should be set correctly.');
		$this->assertEquals('products', $join->Table, 'Table name should be set correctly.');
		$this->assertEquals('products.category_id = categories.id', $join->On, 'ON clause should be set correctly.');
		$this->assertNull($join->Alias, 'Alias should be null if not provided.');
	}

	public function testInvalidJoinType() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid JOIN type: INVALID. Valid types are: INNER, LEFT, RIGHT, FULL, CROSS');

		new Join('INVALID', 'table', 'table.id = other_table.id');
	}

	public function testInvalidTableName() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Table name must be a non-empty string.');

		new Join('LEFT', '', 'table.id = other_table.id');
	}

	public function testInvalidOnClause() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('ON clause must be a non-empty string.');

		new Join('LEFT', 'table', '');
	}

	public function testJoinPropertiesAreSettableAndGettable() {
		$join = new Join('LEFT', 'users', 'users.id = orders.user_id', 'u');

		// Change and test Type
		$join->Type = 'INNER';
		$this->assertEquals('INNER', $join->Type, 'Join type should be updated correctly.');

		// Change and test Table
		$join->Table = 'products';
		$this->assertEquals('products', $join->Table, 'Table name should be updated correctly.');

		// Change and test ON clause
		$join->On = 'products.category_id = categories.id';
		$this->assertEquals('products.category_id = categories.id', $join->On, 'ON clause should be updated correctly.');

		// Change and test Alias
		$join->Alias = 'p';
		$this->assertEquals('p', $join->Alias, 'Alias should be updated correctly.');

		// Test setting Alias to null
		$join->Alias = null;
		$this->assertNull($join->Alias, 'Alias should be set to null.');
	}
}
