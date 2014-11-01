<?php
namespace Fluxoft\Rebar;


class ControllerTest extends \PHPUnit_Framework_TestCase
{
	/** @var Container */
	protected $container;

	protected $request;
	protected $response;
	protected $auth;

	protected function setup() {
		$this->request  = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->response = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
		unset($this->request);
		unset($this->response);
	}

	public function testRoute() {
		$this->assertNotEquals('foo', 'bar');
	}
}
 