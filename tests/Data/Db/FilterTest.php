<?php

namespace Fluxoft\Rebar\Data\Db;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase {
	// Test the interface implementation
	public function testGetProperty() {
		$filter = new Filter('property', '=', 'value');
		$this->assertEquals('property', $filter->GetProperty());
	}
	public function testGetOperator() {
		$filter = new Filter('property', '=', 'value');
		$this->assertEquals('=', $filter->GetOperator());
	}
	public function testGetValue() {
		$filter = new Filter('property', '=', 'value');
		$this->assertEquals('value', $filter->GetValue());
	}
	public function testCreateStaticMethod() {
		$filter = Filter::Create('property', '=', 'value');
		$this->assertInstanceOf(Filter::class, $filter);
		$this->assertEquals('property', $filter->GetProperty());
		$this->assertEquals('=', $filter->GetOperator());
		$this->assertEquals('value', $filter->GetValue());
	}

	public function testSetInvalidOperator() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'fail', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForInOperator() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'IN', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorNotArray() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorArrayTooShort() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', ['low']);
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorArrayTooLong() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', ['low', 'high', 'higher']);
		unset($filter);
	}
	public function testSetInvalidValueArray() {
		$this->expectException('\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', '=', ['value']);
		unset($filter);
	}
	public function testSetValueForIsOperator() {
		$filter = new Filter('property', 'IS', null);

		$this->assertNull($filter->GetValue(), 'Value for IS operator should be NULL.');
	}
	public function testSetValueForIsNotOperator() {
		$filter = new Filter('property', 'IS NOT', null);

		$this->assertNull($filter->GetValue(), 'Value for IS NOT operator should be NULL.');
	}
	public function testSetInvalidValueForIsOperator() {
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException::class);
		$this->expectExceptionMessage("Filter operator 'IS' requires the value to be NULL.");

		new Filter('property', 'IS', 'not null');
	}
	public function testSetValueForInOperator() {
		$filter = new Filter('property', 'IN', ['value1', 'value2']);

		$this->assertEquals(['value1', 'value2'], $filter->GetValue(), 'Value for IN operator should be an array.');
	}
	public function testSetInvalidValueForNotInOperator() {
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException::class);
		$this->expectExceptionMessage("Filter operator set to NOT IN. Value must be an array of values.");

		new Filter('property', 'NOT IN', 'not an array');
	}
	public function testSetValueForNotInOperator() {
		$filter = new Filter('property', 'NOT IN', ['value1', 'value2']);

		$this->assertEquals(['value1', 'value2'], $filter->GetValue(), 'Value for NOT IN operator should be an array.');
	}
	public function testSetValueForBetweenOperator() {
		$filter = new Filter('property', 'BETWEEN', [10, 20]);

		$this->assertEquals([10, 20], $filter->GetValue(), 'Value for BETWEEN operator should be an array of two values.');
	}
	public function testSetInvalidValueForBetweenOperatorTooFew() {
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException::class);
		$this->expectExceptionMessage(
			"Filter operator set to BETWEEN. Value must be an array with two elements, e.g. [\$low, \$high]."
		);

		new Filter('property', 'BETWEEN', [10]);
	}
	public function testSetInvalidValueForBetweenOperatorTooMany() {
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException::class);
		$this->expectExceptionMessage(
			"Filter operator set to BETWEEN. Value must be an array with two elements, e.g. [\$low, \$high]."
		);

		new Filter('property', 'BETWEEN', [10, 20, 30]);
	}
	public function testSetInvalidArrayValueForEqualsOperator() {
		$this->expectException(\Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException::class);
		$this->expectExceptionMessage("Cannot set Value to an array for the given Operator");

		new Filter('property', '=', ['invalid array']);
	}
	public function testSetValueForEqualsOperator() {
		$filter = new Filter('property', '=', 'valid value');

		$this->assertEquals('valid value', $filter->GetValue(), 'Value for = operator should be set correctly.');
	}
}
