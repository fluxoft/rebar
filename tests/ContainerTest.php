<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
