<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\AccessDeniedException;
use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;
use Fluxoft\Rebar\Exceptions\RouterException;
use Fluxoft\Rebar\Exceptions\AuthenticationException;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

/**
 * Class Router
 * @package Fluxoft\Rebar
 * @property string ControllerNamespace
 * @property array SetupArgs
 * @property array MethodArgs
 * @property array CleanupArgs
 */
class Router extends Model {
	protected $properties = [
		'ControllerNamespace' => '',
		'SetupArgs' => [],
		'MethodArgs' => [],
		'CleanupArgs' => []
	];

	/** @var Route[] */
	protected $routes = [];

	/** @var array */
	protected $authTypes = [];

	/** @var string */
	protected $controllerNamespace;

	/**
	 * @param array $controllerNamespace The namespace where this app's controllers are found.
	 * @param array $setupArgs An array of properties to be passed to each Controller's Setup method.
	 * @param array $methodArgs An array of properties that should be passed to each Controller method when called,
	 * passed in before the URL params.
	 * @param array $cleanupArgs An array of properties to be passed to each Controller's Cleanup method.
	 */
	public function __construct($controllerNamespace, $setupArgs = [], $methodArgs = [], $cleanupArgs = []) {
		$this->controllerNamespace = $controllerNamespace;
		$this->SetupArgs           = $setupArgs;
		$this->MethodArgs          = $methodArgs;
		$this->CleanupArgs         = $cleanupArgs;
	}

	/**
	 * @param Route[] $routes
	 */
	public function AddRoutes(array $routes) {
		foreach ($routes as $route) {
			$this->AddRoute($route);
		}
	}

	/**
	 * @param Route $route
	 */
	public function AddRoute(Route $route) {
		$this->routes[] = $route;
	}

	public function SetAuthForPath(AuthInterface $auth, $path = '/') {
		$this->authTypes[$path] = $auth;
		// reverse key sort this array so that more specific paths are first
		krsort($this->authTypes);
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
		$path = $request->PathInfo;
		$auth = null;
		foreach ($this->authTypes as $route => $authInterface) {
			if (strpos($path, $route) === 0) {
				$auth = $authInterface;
				if (!$auth instanceof AuthInterface) {
					throw new RouterException(sprintf(
						"The authentication type specified for %s must implement AuthInterface",
						$request->PathInfo
					));
				}
				break;
			}
		}

		$route = $this->getRoute($path);

		/** @var $controller \Fluxoft\Rebar\Controller */
		$controller = new $route['controller']($request, $response, $auth);

		if (!is_callable([$controller, $route['action']])) {
			throw new RouterException(sprintf(
				'Could not find a method called %s in %s.',
				$route['action'],
				$route['controller']
			));
		}

		if (method_exists($controller, 'Setup')) {
			$this->callControllerMethodWithParams($controller, 'Setup', $this->SetupArgs);
		}

		try {
			$controller->Authorize($route['action']);
		} catch (AccessDeniedException $e) {
			$response->Halt(403, $e->getMessage());
		} catch (MethodNotAllowedException $e) {
			$response->Halt(405, $e->getMessage());
		} catch (CrossOriginException $e) {
			$response->Halt(403, $e->getMessage());
		}

		// If this is an options request, and no exceptions were thrown for Authorize,
		// immediately return a 200 OK and do not even run the controller method.
		if (strtoupper($request->Method) === 'OPTIONS') {
			$response->Halt(200, 'OK');
		}

		/*
		 * Add any configured MethodArgs to the array that will be used to call the controller
		 * method, and then any URL params that were returned.
		 */
		$actionParams = [];
		foreach ($this->MethodArgs as $arg) {
			$actionParams[] = $arg;
		}
		foreach ($route['url'] as $urlParam) {
			$actionParams[] = $urlParam;
		}
		$this->callControllerMethodWithParams($controller, $route['action'], $actionParams);
		$controller->Display();

		if (method_exists($controller, 'Cleanup')) {
			$this->callControllerMethodWithParams($controller, 'Cleanup', $this->CleanupArgs);
		}
	}

	protected function callControllerMethodWithParams(Controller $controller, $method, array $params) {
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

				/*
				 * remember to add class checking back in here after Route stuff
				 */



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
				//foreach ($pathParts as $part) {
				$controllerClass .= '\\'.ucwords(array_shift($pathParts));
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
