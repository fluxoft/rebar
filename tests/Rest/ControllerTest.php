<?php

namespace Fluxoft\Rebar\Rest;

use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
