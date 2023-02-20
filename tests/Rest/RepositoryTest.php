<?php

namespace Fluxoft\Rebar\Rest;

use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase {
	protected function setup():void {

	}

	protected function teardown():void {

	}

	public function testFooNotEqualBar() {
		$this->assertNotEquals('foo', 'bar');
	}
}
