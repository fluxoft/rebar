<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class ParameterSetTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
