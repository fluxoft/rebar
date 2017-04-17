<?php

namespace Fluxoft\Rebar\Db;

use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
