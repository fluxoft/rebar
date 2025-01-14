<?php

namespace Fluxoft\Rebar\Http;

use App\Controllers\TestController;
use App\Controllers\ValidController;
use App\ValidClass;
use Fluxoft\Rebar\Auth\Basic;
use Fluxoft\Rebar\Exceptions\RouterException;
use Fluxoft\Rebar\Http\Middleware\MiddlewareInterface;
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

	protected function setup(): void {
		$this->authMock           = $this->getMockBuilder('\\Fluxoft\\Rebar\\Auth\\Basic')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver    = $this->getMockBuilder('\\Fluxoft\\Rebar\\Http\\Request')
			->disableOriginalConstructor()
			->getMock();
		$this->responseObserver   = $this->getMockBuilder('\\Fluxoft\\Rebar\\Http\\Response')
			->disableOriginalConstructor()
			->getMock();
		$this->controllerObserver = $this->getMockBuilder('\\Fluxoft\\Rebar\\Http\\Controller')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown(): void {
		unset($this->authMock);
		unset($this->requestObserver);
		unset($this->responseObserver);
		unset($this->controllerObserver);
	}

	public function testAddRoutes() {
		$router = new TestRouter('\\App\\Controllers\\');

		$this->assertEquals('App\\Controllers', $router->ControllerNamespace);

		$routes = [
			new Route('/', 'Main', 'Index'),
			new Route('/next', 'Next', 'Index'),
			new Route('/foo/bar', 'Foo', 'Bar')
		];

		$router->AddRoutes($routes);

		$this->assertEquals($routes, $router->GetRoutes());
	}

	public function testAddMiddleware() {
		$router = new TestRouter('\\App\\Controllers');
		/** @var MiddlewareInterface|MockObject $middleware */
		$middleware = $this->createMock(MiddlewareInterface::class);
		$router->AddMiddleware($middleware);
		$this->assertCount(1, $router->GetMiddlewareStack());
	}

	public function testSetMiddlewareStack() {
		$router = new TestRouter('\\App\\Controllers');
		/** @var MiddlewareInterface|MockObject $middleware1 */
		$middleware1 = $this->createMock(MiddlewareInterface::class);
		/** @var MiddlewareInterface|MockObject $middleware2 */
		$middleware2 = $this->createMock(MiddlewareInterface::class);

		$router->SetMiddlewareStack([$middleware1, $middleware2]);

		$this->assertCount(2, $router->GetMiddlewareStack());
	}

	public function testSetMiddlewareStackThrowsExceptionForInvalidMiddleware() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Middleware stack must be an array of MiddlewareInterface objects.');

		$router = new TestRouter('\\App\\Controllers');
		$router->SetMiddlewareStack(['InvalidMiddleware']);
	}

	public function testProcessMiddlewareStack() {
		$router = new TestRouter('\\App\\Controllers');
		/** @var MiddlewareInterface|MockObject $middleware */
		$middleware = $this->createMock(MiddlewareInterface::class);
		$middleware->expects($this->once())
			->method('Process')
			->willReturn($this->responseObserver);

		$router->AddMiddleware($middleware);

		$processedResponse = $router->PublicProcessMiddleware($this->requestObserver, $this->responseObserver);
		$this->assertSame($this->responseObserver, $processedResponse);
	}

	public function testResolveRouteDefinitionThrowsException() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('The controller \\App\\Controllers\\SomeController specified does not exist.');

		$router = new TestRouter('\\App\\Controllers');
		$route  = new Route('/', 'SomeController', 'Index');
		$router->AddRoute($route);

		$router->PublicResolveRouteDefinition('/');
	}

	public function testResolveIntuitiveRouteThrowsException() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('No controller could be found to handle this request: /invalid/path');

		$router = new TestRouter('\\App\\Controllers');
		$router->PublicResolveIntuitiveRoute('/invalid/path');
	}

	public function testResolveRouteDefinitionWithValidController() {
		$router = new TestRouter('\\App\\Controllers');
		$route  = new Route('/', ValidController::class, 'Index');
		$router->AddRoute($route);

		// Simulate a valid controller class existing
		$controllerClass = '\\App\\Controllers\\ValidController';

		$routeParts = $router->PublicResolveRouteDefinition('/');
		$this->assertEquals($controllerClass, $routeParts['controller']);
		$this->assertEquals('Index', $routeParts['action']);
	}

	public function testResolveRouteDefinitionWithMultipleRoutes() {
		$router = new TestRouter('\\App\\Controllers');
		$route1 = new Route('/test', TestController::class, 'Action1');
		$route2 = new Route('/test2', TestController::class, 'Action2');
		$router->AddRoutes([$route1, $route2]);

		$routeParts1 = $router->PublicResolveRouteDefinition('/test');
		$this->assertEquals('\\App\\Controllers\\TestController', $routeParts1['controller']);
		$this->assertEquals('Action1', $routeParts1['action']);

		$routeParts2 = $router->PublicResolveRouteDefinition('/test2');
		$this->assertEquals('\\App\\Controllers\\TestController', $routeParts2['controller']);
		$this->assertEquals('Action2', $routeParts2['action']);
	}

	/**
	 * This test is hitting a piece of validation code that is unlikely to be hit in real-world usage.
	 * It is included for completeness, in case someone extends Router and manipulates the routes array directly.
	 */
	public function testResolveRouteDefinitionThrowsExceptionForInvalidRoute() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Routes must be instance of the Route class.');

		$router = new TestRouter('\\App\\Controllers');

		// Directly inject an invalid route into the routes array for testing
		$invalidRoute = new \stdClass(); // Not a Route instance
		$reflection   = new \ReflectionClass($router);
		$property     = $reflection->getProperty('routes');
		$property->setAccessible(true);
		$property->setValue($router, [$invalidRoute]);

		$router->PublicResolveRouteDefinition('/');
	}

	public function testProcessMiddlewareModifiesRequest() {
		$router           = new TestRouter('\\App\\Controllers');
		$modifiedResponse = $this->createMock(Response::class);

		/** @var MiddlewareInterface|MockObject $middleware */
		$middleware = $this->createMock(MiddlewareInterface::class);
		$middleware->expects($this->once())
			->method('Process')
			->willReturn($modifiedResponse);

		$router->AddMiddleware($middleware);

		$processedResponse = $router->PublicProcessMiddleware($this->requestObserver, $this->responseObserver);
		$this->assertSame($modifiedResponse, $processedResponse);
	}

	public function testResolveRouteDefinitionWithSimilarPaths() {
		$router = new TestRouter('\\App\\Controllers');
		$route1 = new Route('/test', TestController::class, 'Action1');
		$route2 = new Route('/test/extra', TestController::class, 'Action2');
		$router->AddRoutes([$route1, $route2]);

		$routeParts1 = $router->PublicResolveRouteDefinition('/test');
		$this->assertEquals('\\App\\Controllers\\TestController', $routeParts1['controller']);
		$this->assertEquals('Action1', $routeParts1['action']);

		$routeParts2 = $router->PublicResolveRouteDefinition('/test/extra');
		$this->assertEquals('\\App\\Controllers\\TestController', $routeParts2['controller']);
		$this->assertEquals('Action2', $routeParts2['action']);
	}

	public function testPathExceedingMaxDepthThrowsException() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Exceeded maximum controller nesting depth of 6.');

		$router   = new TestRouter('\\App\\Controllers');
		$longPath = str_repeat('/deep', 7);
		$router->PublicResolveIntuitiveRoute($longPath);
	}

	/**
	 * @dataProvider intuitiveRouteProvider
	 */
	public function testResolveIntuitiveRouteResolvesControllerAndAction(
		$path,
		$expectedController,
		$expectedAction,
		$expectedParams
	) {
		$router = new TestRouter('App\\Controllers');

		$routeParts = $router->PublicResolveIntuitiveRoute($path);

		$this->assertEquals($expectedController, $routeParts['controller']);
		$this->assertEquals($expectedAction, $routeParts['action']);
		$this->assertEquals($expectedParams, $routeParts['url']);
	}

	public function intuitiveRouteProvider() {
		return [
			'Case: Default action and no params' => [
				'/validController/default',
				'\\App\\Controllers\\ValidController',
				'Default',
				[]
			],
			'Case: Nested controller with params' => [
				'/package/contents/default/one/two',
				'\\App\\Controllers\\Package\\Contents',
				'Default',
				['one', 'two']
			],
			'Case: Main controller with index action' => [
				'/main/index',
				'\\App\\Controllers\\Main',
				'Index',
				[]
			],
			'Case: Main controller with extra params' => [
				'/main/default/param1/param2',
				'\\App\\Controllers\\Main',
				'Default',
				['param1', 'param2']
			],
			'Case: Default fallback to main/index' => [
				'/',
				'\\App\\Controllers\\Main',
				'Default',
				[]
			],
			'Case: Single path segment treated as controller and default action' => [
				'/singleController',
				'\\App\\Controllers\\SingleController',
				'Default',
				[]
			],
			'Case: Route to controller with no action, but with a param' => [
				'/singleController/param1',
				'\\App\\Controllers\\SingleController',
				'Default',
				['param1']
			],
		];
	}

	public function testResolveControllerThrowsExceptionForNonControllerClass() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage(
			'Controller class \\App\\Controllers\\InvalidController must extend Fluxoft\\Rebar\\Http\\Controller.'
		);

		$router = new TestRouter('\\App\\Controllers');

		// Simulate a class that exists but does not extend Controller
		eval('namespace App\\Controllers; class InvalidController {}');
		$router->PublicResolveController('InvalidController');
	}

	public function testResolveControllerWithNamespaceAndNonExistentClass() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('The controller \\App\\Controllers\\NonExistentController specified does not exist.');

		$router = new TestRouter('\\App\\Controllers');
		$router->PublicResolveController('NonExistentController');
	}

	public function testResolveControllerWithEmptyNamespace() {
		$router = new TestRouter('');

		// Simulate a valid FQCN without a namespace
		eval('class GlobalController extends \\Fluxoft\\Rebar\\Http\\Controller {}');

		$resolvedController = $router->PublicResolveController('GlobalController');
		$this->assertEquals('\\GlobalController', $resolvedController);
	}

	public function testResolveControllerWithNamespaceAndValidController() {
		$router = new TestRouter('\\App\\Controllers');

		$resolvedController = $router->PublicResolveController('ValidController');
		$this->assertEquals('\\App\\Controllers\\ValidController', $resolvedController);
	}

	public function testResolveControllerThrowsExceptionForValidFQCNNonControllerClass() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Controller class \\App\\ValidClass must extend Fluxoft\\Rebar\\Http\\Controller.');

		$router = new TestRouter('\\App\\Controllers');

		$router->PublicResolveController(ValidClass::class);
	}

	public function testResolveControllerWithoutNamespace() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('The controller \\TestController specified does not exist.');

		// Instantiate a TestRouter with an empty ControllerNamespace
		$router = new TestRouter('');

		// Attempt to resolve a controller without a namespace
		$router->PublicResolveController('TestController');
	}

	public function testRouteCallsControllerLifecycleMethods() {
		// Reset the called methods before the test
		TestController::resetCalledMethods();

		$router = new TestRouter('App\\Controllers');

		// Add a route pointing to the TestController
		$route = new Route('/test', TestController::class, 'Action1');
		$router->AddRoute($route);

		$this->requestObserver
			->expects($this->once())
			->method('__get')
			->with('Path')
			->willReturn('/test');

		// Execute the Route method
		$router->Route($this->requestObserver, $this->responseObserver);

		// Assert that the lifecycle methods were called
		$this->assertContains('Setup', TestController::$calledMethods);
		$this->assertContains('Action1', TestController::$calledMethods);
		$this->assertContains('Cleanup', TestController::$calledMethods);
	}

	public function testInstantiateControllerCreatesValidInstance() {
		$router     = new TestRouter('App\\Controllers');
		$controller = $router->PublicInstantiateController(
			TestController::class,
			$this->requestObserver,
			$this->responseObserver
		);

		$this->assertInstanceOf(TestController::class, $controller);
	}

	public function testInstantiateControllerThrowsExceptionForInvalidClass() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Controller InvalidClass does not exist.');

		$router = new TestRouter('App\\Controllers');
		$router->PublicInstantiateController('InvalidClass', $this->requestObserver, $this->responseObserver);
	}

	public function testInstantiateControllerThrowsExceptionForNonControllerClass() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Controller App\InvalidClass must extend Fluxoft\Rebar\Http\Controller.');

		$router = new TestRouter('App\\Controllers');
		$router->PublicInstantiateController(\App\InvalidClass::class, $this->requestObserver, $this->responseObserver);
	}

	public function testInvokeActionCallsLifecycleMethods() {
		$router = new TestRouter('App\\Controllers');

		/** @var TestController|MockObject $controllerMock */
		$controllerMock = $this->createMock(TestController::class);
		$controllerMock->expects($this->once())
			->method('Setup');
		$controllerMock->expects($this->once())
			->method('Action1');
		$controllerMock->expects($this->once())
			->method('Cleanup');

		$router->PublicInvokeAction($controllerMock, 'Action1', ['param1', 'param2']);
	}

	public function testInvokeActionThrowsExceptionForInvalidAction() {
		$this->expectException(RouterException::class);
		$this->expectExceptionMessage('Could not find a method called Action3 in App\\Controllers\\TestController.');

		$router = new TestRouter('App\\Controllers');

		$controller = new TestController($this->requestObserver, $this->responseObserver);

		// Call a non-existent action
		$router->PublicInvokeAction($controller, 'Action3', []);
	}

	public function testGetRouteWithDefinedRoute() {
		$router = new TestRouter('App\\Controllers');
		$route  = new Route('/defined', TestController::class, 'DefinedAction');
		$router->AddRoute($route);

		$routeParts = $router->PublicGetRoute('/defined');

		$this->assertEquals('\\App\\Controllers\\TestController', $routeParts['controller']);
		$this->assertEquals('DefinedAction', $routeParts['action']);
		$this->assertEmpty($routeParts['url']);
	}

	public function testGetRouteWithIntuitiveRoute() {
		$router     = new TestRouter('App\\Controllers');
		$routeParts = $router->PublicGetRoute('/main');

		$this->assertEquals('\\App\\Controllers\\Main', $routeParts['controller']);
		$this->assertEquals('Default', $routeParts['action']);
		$this->assertEmpty($routeParts['url']);
	}

	public function testCallControllerMethodWithParams() {
		$router = new TestRouter('App\\Controllers');

		/** @var TestController|MockObject $controllerMock */
		$controllerMock = $this->createMock(TestController::class);
		$controllerMock->expects($this->once())
			->method('Action1')
			->with('param1', 'param2');

		$router->PublicCallControllerMethodWithParams($controllerMock, 'Action1', ['param1', 'param2']);
	}

	public function testRouteHandlesRouterException() {
		$router = new TestRouterGetRouteThrowsException('\\App\\Controllers');

		// Mock request and response
		$this->requestObserver
			->method('__get')
			->with('Path')
			->willReturn('/invalid/path');

		$this->responseObserver
			->expects($this->once())
			->method('AddHeader')
			->with('Content-Type', 'text/plain');

		$this->responseObserver
			->expects($this->exactly(2))
			->method('__set')
			->willReturnCallback(function ($key, $value) {
				if ($key === 'Status') {
					$this->assertEquals(404, $value);
				} elseif ($key === 'Body') {
					$this->assertStringContainsString('Route not found', $value);
				}
			});

		$router->Route($this->requestObserver, $this->responseObserver);
	}
}

// TestRouter with public access to protected methods
// @codingStandardsIgnoreStart
class TestRouter extends Router {
	public function GetRoutes() {
		return $this->routes;
	}
	public function GetMiddlewareStack() {
		return $this->middlewareStack;
	}
	public function PublicGetRoute(string $path) {
		return $this->getRoute($path);
	}
	public function PublicResolveRouteDefinition($path) {
		return $this->resolveRouteDefinition($path);
	}
	public function PublicProcessMiddleware(Request $request, Response $response) {
		return $this->processMiddleware($request, $response);
	}
	public function PublicResolveIntuitiveRoute(string $path): array {
		return $this->resolveIntuitiveRoute($path);
	}
	public function PublicResolveController(string $controller): ?string {
		return $this->resolveController($controller);
	}
	public function PublicCallControllerMethodWithParams(Controller $controller, string $method, array $params): void {
		$this->callControllerMethodWithParams($controller, $method, $params);
	}
	public function PublicInstantiateController(string $controllerClass, Request $request, Response $response): Controller {
		return $this->instantiateController($controllerClass, $request, $response);
	}
	public function PublicInvokeAction(Controller $controller, string $action, array $params): void {
		$this->invokeAction($controller, $action, $params);
	}
}

// TestRouter with overridden route method
class TestRouterGetRouteThrowsException extends Router {
	protected function getRoute(string $path) {
		echo "calling TestRouterGetRouteThrowsException::getRoute\n";
		throw new RouterException('Route not found', 404);
	}
}

class TestRouterOverrideRoute extends Router {
	public ?Controller $controller = null;
}

namespace App;

class ValidClass {}
class InvalidClass {}

namespace App\Controllers;

use Fluxoft\Rebar\Http\Controller;

class ValidController extends Controller {
	public function Index() {}
}
class TestController extends Controller {
	public static array $calledMethods = [];

	public function Setup() {
		self::$calledMethods[] = 'Setup';
	}

	public function Cleanup() {
		self::$calledMethods[] = 'Cleanup';
	}

	public function Action1() {
		self::$calledMethods[] = 'Action1';
	}

	public function Action2() {
		self::$calledMethods[] = 'Action2';
	}

	// Reset the called methods (useful for test isolation)
	public static function resetCalledMethods() {
		self::$calledMethods = [];
	}
}
class SingleController extends Controller {
	public function Index() {}
}
class Main extends Controller {
	public function Default() {}
	public function Index() {}
}

namespace App\Controllers\Package;

use Fluxoft\Rebar\Http\Controller;

class Contents extends Controller {
	public function Default() {}
}
// @codingStandardsIgnoreEnd
