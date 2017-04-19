<?php

namespace Fluxoft\Rebar\Db;

use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
