<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\Web;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
	/** @var Request */
	protected $request;
	/** @var Response */
	protected $response;
	/** @var Web */
	protected $webAuth;
	/** @var Controller */
	protected $controller;

	protected function setup() {
		$this->request  = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->response = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->webAuth  = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new DummyController($this->request, $this->response, $this->webAuth);
	}
	protected function teardown() {
		unset($this->request);
		unset($this->response);
	}

	public function testSetup() {
		$this->controller->Setup();
	}

	public function testDisplay() {
		$this->controller->Display();
	}

	public function testDenyAccess() {
		$this->controller->DenyAccess('denied');
	}
}

class DummyController extends Controller {
	public function GetRequest() {
		return $this->request;
	}
	public function GetResponse() {
		return $this->response;
	}
	public function GetAuth() {
		return $this->auth;
	}
}
 