<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use Fluxoft\Rebar\Exceptions\AuthenticationException;
use Fluxoft\Rebar\Exceptions\RouterException;
use Fluxoft\Rebar\Http\Middleware\MiddlewareInterface;

/**
 * Class Router
 * @package Fluxoft\Rebar
 * @property string $ControllerNamespace
 * @property array  $SetupArgs
 * @property array  $MethodArgs
 * @property array  $CleanupArgs
 * @property MiddlewareInterface[] $middlewareStack
 */
class Router {
	use GettableProperties;
	use SettableProperties;

	/** @var Route[] */
	protected $routes = [];

	/** @var array */
	protected $authTypes = [];

	/** @var MiddlewareInterface[] */
	protected $middlewareStack = []; // Existing middlewareStack property

	protected int $maxDepth = 6;

	/**
	 * @param string $controllerNamespace The namespace where this app's controllers are found.
	 * @param array  $setupArgs An array of properties to be passed to each Controller's Setup method.
	 * @param array  $methodArgs An array of properties that should be passed to each Controller method when called,
	 * passed in before the URL params.
	 * @param array  $cleanupArgs An array of properties to be passed to each Controller's Cleanup method.
	 */
	public function __construct(
		string $controllerNamespace,
		array $setupArgs = [],
		array $methodArgs = [],
		array$cleanupArgs = [],
		int $maxDepth = 6
	) {
		$this->properties          = [
			'ControllerNamespace' => '',
			'SetupArgs'           => '',
			'MethodArgs'          => '',
			'CleanupArgs'         => ''
		];
		$this->ControllerNamespace = $controllerNamespace;
		$this->SetupArgs           = $setupArgs;
		$this->MethodArgs          = $methodArgs;
		$this->CleanupArgs         = $cleanupArgs;
		$this->maxDepth            = $maxDepth;
	}

	/**
	 * @param Route[] $routes
	 */
	public function AddRoutes(array $routes) {
		foreach ($routes as $route) {
			$this->AddRoute($route);
		}
	}
	public function AddRoute(Route $route) {
		$this->routes[] = $route;
	}

	public function AddMiddleware(MiddlewareInterface $middleware) {
		$this->middlewareStack[] = $middleware;
	}

	public function SetMiddlewareStack(array $middlewareStack) {
		foreach ($middlewareStack as $middleware) {
			if (!($middleware instanceof MiddlewareInterface)) {
				throw new RouterException('Middleware stack must be an array of MiddlewareInterface objects.');
			}
		}
		$this->middlewareStack = $middlewareStack;
	}

	/**
	 * Route to the appropriate controller/method combination for a Request.
	 *
	 * If no custom routes were added, or a matching route is not found, the default routing behavior
	 * is to split the path, then try to find a valid controller by successively concatenating the
	 * parts of the path together to form namespaced classes by appending the path parts to the
	 * ControllerNamespace until a valid Controller class is found.
	 *
	 * For instance, a Request for the path "/bundle/controller/action/param1/param2" would first try
	 * to find a class called \ControllerNamespace\Bundle and failing that, would then try to find
	 * \ControllerNamespace\Bundle\Controller and finding that is a valid class, would then call its
	 * Action method, passing in the "param1" and "param2" as method parameters (after any MethodArgs
	 * that might have been configured).
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws RouterException
	 * @throws AuthenticationException
	 */
	public function Route(Request $request, Response $response): void {
		try {
			$this->processMiddleware($request, $response);

			$route = $this->getRoute($request->Path);

			// Instantiate controller
			$controller = $this->instantiateController($route['controller'], $request, $response);

			// Invoke lifecycle and action
			$this->invokeAction($controller, $route['action'], $route['url']);

			// Send the response using the data that the controller should have set.
			$response->Send();
		} catch (RouterException $e) {
			$response->Status = 404;
			$response->AddHeader('Content-Type', 'text/plain');
			$response->Body = "Route not found\n" . $e->getMessage();
			$response->Send();
		}
	}

	protected function instantiateController(string $controllerClass, Request $request, Response $response): Controller {
		if (!class_exists($controllerClass)) {
			throw new RouterException(sprintf('Controller %s does not exist.', $controllerClass));
		}

		$controller = new $controllerClass($request, $response);
		if (!$controller instanceof Controller) {
			throw new RouterException(sprintf('Controller %s must extend %s.', $controllerClass, Controller::class));
		}

		return $controller;
	}

	protected function invokeAction(Controller $controller, string $action, array $params): void {
		if (!is_callable([$controller, $action])) {
			throw new RouterException(sprintf(
				'Could not find a method called %s in %s.',
				$action,
				get_class($controller)
			));
		}

		if (method_exists($controller, 'Setup')) {
			$this->callControllerMethodWithParams($controller, 'Setup', $this->SetupArgs);
		}

		$actionParams = [...$this->MethodArgs, ...$params];
		$this->callControllerMethodWithParams($controller, $action, $actionParams);

		if (method_exists($controller, 'Cleanup')) {
			$this->callControllerMethodWithParams($controller, 'Cleanup', $this->CleanupArgs);
		}
	}

	/** Setter for ControllerNamespace property that will trim any leading or trailing backslashes.
	 * @param string $controllerNamespace
	 */
	protected function setControllerNamespace(string $controllerNamespace): void {
		$this->properties['ControllerNamespace'] = trim($controllerNamespace, '\\');
	}

	/**
	 * Process each middleware in the stack.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	protected function processMiddleware(Request $request, Response $response): Response {
		$middlewareStack = $this->middlewareStack;

		$next = function() use ($request, $response, &$middlewareStack, &$next) {
			$middleware = array_shift($middlewareStack);
			if ($middleware) {
				return $middleware->Process($request, $response, $next);
			}
			return $response;
		};

		return $next();
	}

	protected function callControllerMethodWithParams(Controller $controller, string $method, array $params): void {
		call_user_func_array([$controller, $method], $params);
	}

	protected function getRoute(string $path) {
		$routeParts = [];

		// First try to resolve using explicit Route definitions
		$routeParts = $this->resolveRouteDefinition($path);

		// If no match, fall back to intuitive routing rules
		if (empty($routeParts)) {
			$routeParts = $this->resolveIntuitiveRoute($path);
		}

		return $routeParts;
	}

	protected function resolveRouteDefinition(string $path): array {
		$routeParts = [];

		if (!empty($this->routes)) {
			// Sort routes by descending path length to prioritize specific matches
			usort($this->routes, function (Route $a, Route $b) {
				return strlen($b->Path) - strlen($a->Path);
			});

			foreach ($this->routes as $route) {
				if (!($route instanceof Route)) {
					throw new RouterException('Routes must be instance of the Route class.');
				}

				$pattern = '/^' . str_replace('/', '\/', $route->Path) . '(\/[A-Za-z0-9\-.]+)*\/*$/';
				if (preg_match($pattern, $path)) {
					$routeParts['controller'] = $this->resolveController($route->Controller);
					$routeParts['action']     = $route->Action;
					$paramsPath               = substr($path, strlen($route->Path) + 1);
					$routeParts['url']        = array_filter(explode('/', $paramsPath));
					break;
				}
			}
		}

		return $routeParts;
	}

	protected function resolveIntuitiveRoute(string $path): array {
		$normalizedPath = preg_replace('#/+#', '/', trim($path, '/'));

		// Default to ['main', 'default'] if path is empty or single slash
		if (strlen($normalizedPath) <= 1) {
			$pathParts = ['main', 'default'];
		} else {
			$pathParts = array_filter(explode('/', $normalizedPath), fn($var) => !empty($var));
			// Add 'default' if only one part is present
			if (count($pathParts) === 1) {
				$pathParts[] = 'default';
			}
		}

		// Check the maximum depth constraint
		if (count($pathParts) > $this->maxDepth) {
			throw new RouterException(sprintf(
				'Exceeded maximum controller nesting depth of %d.',
				$this->maxDepth
			));
		}

		$controllerClass = (!empty($this->ControllerNamespace)) ? '\\' . $this->ControllerNamespace : '';
		$routeParts      = [];

		// Try to find a valid controller class
		while (!empty($pathParts)) {
			$pathPart = array_shift($pathParts);

			$controllerClass .= '\\' . ucwords($pathPart);

			if (class_exists($controllerClass)) {
				$routeParts['controller'] = $controllerClass;
				break;
			}
		}

		if (!isset($routeParts['controller'])) {
			throw new RouterException(sprintf(
				'No controller could be found to handle this request: %s',
				$path
			));
		}

		// Check if the next part is a valid method; otherwise, use 'Default'
		$nextPart = !empty($pathParts) ? ucwords(array_shift($pathParts)) : 'Default';

		if (method_exists($routeParts['controller'], $nextPart)) {
			$routeParts['action'] = $nextPart;
		} else {
			$routeParts['action'] = 'Default';

			// If the next part was not a valid method, treat it as a parameter
			if ($nextPart !== 'Default') {
				array_unshift($pathParts, strtolower($nextPart));
			}
		}

		$routeParts['url'] = $pathParts;

		return $routeParts;
	}

	/**
	 * Combines controller resolution logic.
	 */
	protected function resolveController(string $controller): ?string {
		// Trim leading backslashes for normalization
		$controller = ltrim($controller, '\\');

		// If a valid FQCN is passed, validate and return it
		if (class_exists($controller)) {
			if (!is_subclass_of($controller, Controller::class)) {
				throw new RouterException(sprintf(
					'Controller class \\%s must extend %s.',
					$controller,
					Controller::class
				));
			}
			return '\\' . $controller;
		}

		// Otherwise, append to the namespace and validate
		$controllerClass = (!empty($this->ControllerNamespace))
			? '\\' . $this->ControllerNamespace . '\\' . $controller
			: '\\' . $controller;

		if (class_exists($controllerClass)) {
			if (!is_subclass_of($controllerClass, Controller::class)) {
				throw new RouterException(sprintf(
					'Controller class %s must extend %s.',
					$controllerClass,
					Controller::class
				));
			}
			return $controllerClass;
		}

		// If no valid class is found, throw the exception
		throw new RouterException(sprintf(
			'The controller %s specified does not exist.',
			$controllerClass
		));
	}
}
