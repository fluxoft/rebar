<?php

namespace Fluxoft\Rebar\Presenters;

use PHPUnit\Framework\TestCase;

class SmartyTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
