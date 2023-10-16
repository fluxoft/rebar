<?php

namespace Fluxoft\Rebar\Db;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase {
	public function testSetInvalidOperator() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'fail', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForInOperator() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'IN', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorNotArray() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', 'value');
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorArrayTooShort() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', ['low']);
		unset($filter);
	}
	public function testSetInvalidValueForBetweenOperatorArrayTooLong() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', 'BETWEEN', ['low', 'high', 'higher']);
		unset($filter);
	}
	public function testSetInvalidValueArray() {
		$this->expectException('\Fluxoft\Rebar\Db\Exceptions\InvalidFilterException');

		$filter = new Filter('property', '=', ['value']);
		unset($filter);
	}
}
