<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\Web;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase {
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

	public function testDisplay() {
		$this->controller->Display();
	}
}

// Ignore the phpcs warning about two classes in one file
// @codingStandardsIgnoreStart
class DummyController extends Controller {
	// @codingStandardsIgnoreEnd
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
