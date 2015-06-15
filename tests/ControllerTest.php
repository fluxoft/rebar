<?php
namespace Fluxoft\Rebar;


class ControllerTest extends \PHPUnit_Framework_TestCase
{
	protected $request;
	protected $response;

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

	public function testDisplay() {
		$stub = $this->getMockForAbstractClass(
			'\\Fluxoft\\Rebar\\Controller',
			array(
				$this->request,
				$this->response
			)
		);

		$stub->Display();
	}

	public function testDenyAccess() {
		$stub = $this->getMockForAbstractClass(
			'\\Fluxoft\\Rebar\\Controller',
			array(
				$this->request,
				$this->response
			)
		);

		$stub->DenyAccess('denied');
	}
}
 