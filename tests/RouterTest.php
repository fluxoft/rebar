<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 6/3/14
 * Time: 4:45 PM
 */

namespace Fluxoft\Rebar;

class RouterTest extends \PHPUnit_Framework_TestCase {
	protected function setup() {}

	protected function teardown() {}

	public function testRoute() {
		$this->assertNotEquals('foo', 'bar');
	}
}
