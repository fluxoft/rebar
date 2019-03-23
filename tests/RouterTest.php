<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $authMock;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $requestObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $responseObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $controllerObserver;
	protected function setup() {
		$this->authMock           = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Basic')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver    = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->responseObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->controllerObserver = $this->getMockBuilder('\Fluxoft\Rebar\Controller')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown() {
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

	/*
	 * The Route method should be refactored to make it simpler and more straightforward, so I'm going to kick the can
	 * on writing this unit test until I have time to rewrite it. For one thing, there are several places where the
	 * Route and AuthTypes are tested to insure they are the right types, but that should happen in AddRoute and
	 * SetAuthTypeForPath.
	 */
	/*public function testRoute() {
		$router = new TestRouter('\Fluxoft\Rebar');

		$this->requestObserver->expects($this->any())
			->method('__get')
			->with('Path')
			->will($this->returnValue('/foo/bar'));

		$router->Route($this->requestObserver, $this->responseObserver);
	}
	public function providerRoute() {

	}*/
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
