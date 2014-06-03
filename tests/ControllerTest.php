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
		$this->container = new Container();
	}

	protected function teardown() {
		unset($this->container);
	}

	public function testAuthenticate() {
		$stub = $this->getMockForAbstractClass(
			'Fluxoft\Rebar\Controller',
			array(
				$this->container
			)
		);
		$stub->expects($this->any())
			->method('Authenticate')
			->will($this->returnValue(true));
	}
}
 