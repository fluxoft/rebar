<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
