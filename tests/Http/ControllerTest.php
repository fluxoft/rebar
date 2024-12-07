<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\Reply;
use Fluxoft\Rebar\Auth\Web;
use Fluxoft\Rebar\Http\Controller;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Presenters\Debug;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 * @package Fluxoft\Rebar
 * @coversDefaultClass \Fluxoft\Rebar\Http\Controller
 */
class ControllerTest extends TestCase
{
	/** @var Request */
	protected $request;
	/** @var Response */
	protected $response;
	/** @var Web */
	protected $webAuth;
	/** @var Debug */
	protected $debugPresenter;

	protected $controller;

	protected function setUp(): void {
		$this->request        = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->response       = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->webAuth        = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
			->disableOriginalConstructor()
			->getMock();
		$this->debugPresenter = $this->getMockBuilder('\Fluxoft\Rebar\Presenters\Debug')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		unset($this->debugPresenter);
		unset($this->webAuth);
		unset($this->request);
		unset($this->response);
	}

	// Add test cases here

}

// @codingStandardsIgnoreStart
// DummyController class definition
class DummyController extends Controller {
	public function SetPresenter($presenter)
	{
		$this->presenter = $presenter;
	}
	public function SetPresenterClass($presenterClass)
	{
		$this->presenterClass = $presenterClass;
	}
	public function PublicSet($key, $value)
	{
		$this->set($key, $value);
	}
	public function PublicGetData()
	{
		return $this->getData();
	}
}
// @codingStandardsIgnoreEnd
