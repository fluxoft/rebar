<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 6/3/14
 * Time: 5:14 PM
 */

namespace Fluxoft\Rebar;


class ControllerTest extends \PHPUnit_Framework_TestCase
{
	/** @var Container */
	protected $container;

	protected function setup() {
	}

	protected function teardown() {
	}

	public function testAuthenticate() {
		$stub = $this->getMockForAbstractClass(
			'Fluxoft\Rebar\Controller'
		);
		$stub->expects($this->any())
			->method('Authenticate')
			->will($this->returnValue(true));
	}
}
 