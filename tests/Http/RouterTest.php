<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Auth\Basic;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Http\Route;
use Fluxoft\Rebar\Http\Router;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
	/** @var Basic|MockObject */
	protected $authMock;
	/** @var Request|MockObject */
	protected $requestObserver;
	/** @var Response|MockObject */
	protected $responseObserver;
	/** @var Controller|MockObject */
	protected $controllerObserver;

 protected function setup():void {
	 $this->authMock           = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Basic')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->requestObserver    = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->responseObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->controllerObserver = $this->getMockBuilder('\Fluxoft\Rebar\Http\Controller')
		 ->disableOriginalConstructor()
		 ->getMock();
 }

 protected function teardown():void {
	 unset($this->authMock);
 }

 public function testAddRoutes() {
	 $router = new TestRouter('\Fluxoft\Rebar');

	 $this->assertEquals('\Fluxoft\Rebar', $router->GetControllerNamespace());

	 $routes = [
		 new Route('/', 'Main', 'Index'),
		 new Route('/next', 'Next', 'Index'),
		 new Route('/foo/bar', 'Foo', 'Bar')
	 ];

	 $router->AddRoutes($routes);

	 $this->assertEquals($routes, $router->GetRoutes());
 }

 public function testSetAuthForPath() {
	 $router = new TestRouter('\Fluxoft\Rebar');

	 $router->SetAuthForPath($this->authMock, '/foo');
	 $router->SetAuthForPath($this->authMock, '/bar');
	 $router->SetAuthForPath($this->authMock, '/foo/bar');
	 $router->SetAuthForPath($this->authMock, '/bar/foo');
	 $router->SetAuthForPath($this->authMock, '/blah');
	 $router->SetAuthForPath($this->authMock, '/');

	 // When added, the Router should store auth paths in reverse key sort order.
	 $expectedKeyOrder = [
		 '/foo/bar',
		 '/foo',
		 '/blah',
		 '/bar/foo',
		 '/bar',
		 '/'
	 ];

	 $this->assertEquals($expectedKeyOrder, array_keys($router->GetAuthTypes()));
 }

	// Additional tests can be added here
}

// @codingStandardsIgnoreStart
class TestRouter extends Router {
    public function GetControllerNamespace() {
  return $this->controllerNamespace;
    }
    public function GetRoutes() {
  return $this->routes;
    }
    public function GetAuthTypes() {
  return $this->authTypes;
    }
}
// @codingStandardsIgnoreEnd
