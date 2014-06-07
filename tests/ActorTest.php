<?php
namespace Fluxoft\Rebar;


class ActorTest extends \PHPUnit_Framework_TestCase
{
	/** @var Container */
	protected $container;

	protected $request;
	protected $response;

	protected function setup() {
		$this->request = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
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

	public function testAuthenticate() {
		$stub = $this->getMockForAbstractClass(
			'Fluxoft\Rebar\Actor',
			array(
				$this->request,
				$this->response
			)
		);
		$stub->expects($this->any())
			->method('Authenticate')
			->will($this->returnValue(true));
	}
}
 