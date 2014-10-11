<?php
namespace Fluxoft\Rebar;


class ActorTest extends \PHPUnit_Framework_TestCase
{
	/** @var Container */
	protected $container;

	protected $request;
	protected $response;
	protected $auth;

	protected function setup() {
		$this->request = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->response = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->auth = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
		unset($this->request);
		unset($this->response);
		unset($this->auth);
	}

	public function testAuthenticate() {
		$stub = $this->getMockForAbstractClass(
			'Fluxoft\Rebar\Actor',
			array(
				$this->request,
				$this->response,
				$this->auth
			)
		);
		$stub->expects($this->any())
			->method('Authenticate')
			->will($this->returnValue(true));
	}
}
 