<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\AccessDeniedException;
use Fluxoft\Rebar\Exceptions\AuthenticationException;
use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;
use Fluxoft\Rebar\Exceptions\RouterException;

/**
 * Class Router
 * @package Fluxoft\Rebar
 * @property string ControllerNamespace
 * @property array SetupArgs
 * @property array MethodArgs
 * @property array CleanupArgs
 * @property MiddlewareInterface[] $middlewareStack
 */
class Router {
	use GettableProperties;
	use SettableProperties;

	/** @var Route[] */
	protected $routes = [];

	/** @var array */
	protected $authTypes = [];

	/** @var string */
	protected $controllerNamespace;

	/** @var MiddlewareInterface[] */
	protected $middlewareStack = []; // Existing middlewareStack property

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
		array$cleanupArgs = []
	) {
		$this->properties = [
			'ControllerNamespace' => $controllerNamespace,
			'SetupArgs'           => $setupArgs,
			'MethodArgs'          => $methodArgs,
			'CleanupArgs'         => $cleanupArgs
		];
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
	public function Route(Request $request, Response $response) {
		// Call processMiddleware and overwrite the $request with the processed Request object
		$request = $this->processMiddleware($request, $response);

		$route = $this->getRoute($request->Path);

		/** @var \Fluxoft\Rebar\Http\Controller $controller */
		$controller = new $route['controller']($request, $response);

		if (!is_callable([$controller, $route['action']])) {
			throw new RouterException(sprintf(
				'Could not find a method called %s in %s.',
				$route['action'],
				$route['controller']
			));
		}

		// Call the Setup method on the controller, if it exists
		if (method_exists($controller, 'Setup')) {
			$this->callControllerMethodWithParams($controller, 'Setup', $this->SetupArgs);
		}

		/*
		 * Add any configured MethodArgs to the array that will be used to call the controller
		 * method, and then any URL params that were returned.
		 */
		$actionParams = [...$this->MethodArgs, ...$route['url']];
		$this->callControllerMethodWithParams($controller, $route['action'], $actionParams);

		// Display the controller output
		$controller->Display();

		// Call the Cleanup method on the controller, if it exists
		if (method_exists($controller, 'Cleanup')) {
			$this->callControllerMethodWithParams($controller, 'Cleanup', $this->CleanupArgs);
		}
	}

	/**
	 * Process each middleware in the stack.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Request
	 */
	protected function processMiddleware(Request $request, Response $response) {
		$middlewareStack = $this->middlewareStack;

		$next = function() use ($request, $response, &$middlewareStack, &$next) {
			$middleware = array_shift($middlewareStack);
			if ($middleware) {
				return $middleware->Process($request, $response, $next);
			}
			return $request;			
		};

		return $next();
	}

	protected function callControllerMethodWithParams(Controller $controller, $method, array $params): void {
		switch (count($params)) {
			case 0:
				$controller->$method();
				break;
			case 1:
				$controller->$method(
					$params[0]
				);
				break;
			case 2:
				$controller->$method(
					$params[0],
					$params[1]
				);
				break;
			case 3:
				$controller->$method(
					$params[0],
					$params[1],
					$params[2]
				);
				break;
			default:
				call_user_func_array([$controller, $method], $params);
				break;
		}
	}

	protected function getRoute($path) {
		$routeParts = [];
		if (isset($this->routes)) {
			/** @var Route $route */
			foreach ($this->routes as $route) {
				if (!($route instanceof Route)) {
					throw new RouterException('Routes must be instance of the Route class.');
				}

				$pattern = '/^'.str_replace('/', '\/', $route->Path).'(\/[A-Za-z0-9\-.]+)*\/*$/';
				if (preg_match($pattern, $path)) {
					$controllerClass  = (strlen($this->controllerNamespace) > 0) ? '\\' . $this->controllerNamespace : '';
					$controllerClass .= '\\'.$route->Controller;

					if (!class_exists($controllerClass)) {
						throw new RouterException(sprintf(
							'The controller %s specified for the path %s does not exist.',
							$controllerClass,
							$route->Path
						));
					}

					$routeParts['controller'] = $controllerClass;
					$routeParts['action']     = $route->Action;
					$paramsPath               = substr($path, strlen($route->Path) + 1);
					$routeParts['url']        = array_filter(explode('/', $paramsPath));
				}
			}
		}
		if (empty($routeParts)) {
			if (strlen($path) > 1) { // disregard leading slash
				$pathParts = array_filter(explode('/', $path), function ($var) {
					return ($var !== null && $var !== false && $var !== '');
				});
				if (count($pathParts) == 1) {
					$pathParts[] = 'index';
				}
			} else {
				$pathParts = array('main','index');
			}

			/*
			 * Try to find a valid controller class using $pathParts by checking for an existing class
			 * for each of the path parts in order. For instance, a $path that yields a $pathParts array
			 * containing ['bundle', 'controller', 'action', 'param1', 'param2'] would first try to find
			 * \ControllerNamespace\Bundle, then \ControllerNamespace\Bundle\Controller until a valid
			 * class is found. If a valid class is not found, throw the exception here.
			 */
			$controllerClass = (strlen($this->controllerNamespace) > 0) ? '\\' . $this->controllerNamespace : '';
			while (!empty($pathParts)) {
				/*
				 * If the $pathPart is 'index' that means we are at the end of a chain, so try changing
				 * that to 'main' so that we can find main/index controllers in a bundle directory.
				 */
				$pathPart = array_shift($pathParts);
				if ($pathPart === 'index') {
					$pathPart = 'main';
				}
				$controllerClass .= '\\'.ucwords($pathPart);
				if (class_exists($controllerClass)) {
					$routeParts['controller'] = $controllerClass;
					break;
				}
			}
			if (!isset($routeParts['controller'])) {
				throw new RouterException('No controller could be found to handle this request.');
			}

			// Add the next value in $pathParts as the action, and the rest as the URL params to use.
			if (empty($pathParts)) {
				$routeParts['action'] = 'Index';
			} else {
				$routeParts['action'] = ucwords(array_shift($pathParts));
			}
			$routeParts['url'] = $pathParts;
		}

		return $routeParts;
	}
}
