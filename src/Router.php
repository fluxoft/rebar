<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\AccessDeniedException;
use Fluxoft\Rebar\Exceptions\RouterException;
use Fluxoft\Rebar\Exceptions\AuthenticationException;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

/**
 * Router class.
 *
 * @author Joe Hart
 *
 */
class Router {
	/** @var array */
	protected $config = [];

	/** @var array */
	protected $routes = [];

	/** @var array */
	protected $auth = [];

	/**
	 * namespace is used to specify the namespace for the app's controllers
	 *
	 * methodArgs allows for the setting of a parameter list
	 * to be sent when calling the routed controller method:
	 *
	 * Custom routes that would not be handled by the default routing behavior can be passed in as a $routes array.
	 *
	 * <code>
	 * $webAuth = new \Fluxoft\Rebar\Auth\Web(...);
	 * $config = array(
	 *     'rootPath' => '/
	 *     'namespace' => 'UserFiles',
	 *     'methodArgs' => array('param1', 'param2')
	 * );
	 * $routes = array(
	 *     '
	 * );
	 * $router = new Router($webAuth, $config, $routes);
	 * </code>
	 * @param array $config
	 * @param array $routes
	 * @param array $auth
	 */
	public function __construct(array $config = [], array $routes = [], array $auth = []) {
		$this->config = $config;
		$this->routes = $routes;
		$this->auth   = $auth;
	}

	/**
	 * Route to the appropriate controller/method combination using the requested path.
	 *
	 * Accepts an optional $routes array that should contain route arrays with path, controller, and method elements.
	 * $routes = array(
	 *	   array(
	 *         'path' => '/container/test',
	 *         'controller' => 'TestController',
	 *         'method' => 'Container'
	 *     )
	 * );
	 *
	 * If $routes is not specified, or a matching route is not found, the default routing behavior is to split the path,
	 * using the first section as the controller name, second as method, and passing the remaining in the url params.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws RouterException
	 * @throws AuthenticationException
	 */
	public function Route(Request $request, Response $response) {
		$path = $request->PathInfo;
		$auth = null;
		foreach ($this->auth as $route => $authInterface) {
			if (strpos($path, $route) === 0) {
				$auth = $authInterface;
				if (!$auth instanceof AuthInterface) {
					throw new RouterException(sprintf(
						"The authenticator for %s must implement AuthInterface",
						$request->PathInfo
					));
				}
				break;
			}
		}

		$route = $this->getRoute($path);

		if (class_exists($route['controller'])) {
			/** @var $controller \Fluxoft\Rebar\Controller */
			$controller = new $route['controller']($request, $response, $auth);
		} else {
			throw new RouterException(sprintf('"%s" was not found.', $route['controller']));
		}
		if (!method_exists($controller, $route['action'])) {
			throw new RouterException(sprintf(
				'Could not find a method called %s in %s.',
				$route['action'],
				$route['controller']
			));
		}

		try {
			$controller->Authorize($route['action']);
		} catch (AccessDeniedException $e) {
			$controller->DenyAccess($e->getMessage());
			exit;
		}

		$actionParams = [];
		if (isset($this->config['methodArgs'])) {
			foreach ($this->config['methodArgs'] as $arg) {
				$actionParams[] = $arg;
			}
		}
		foreach ($route['url'] as $urlParam) {
			$actionParams[] = $urlParam;
		}
		switch (count($actionParams)) {
			case 0:
				$controller->$route['action']();
				break;
			case 1:
				$controller->$route['action'](
					$actionParams[0]
				);
				break;
			case 2:
				$controller->$route['action'](
					$actionParams[0],
					$actionParams[1]
				);
				break;
			case 3:
				$controller->$route['action'](
					$actionParams[0],
					$actionParams[1],
					$actionParams[2]
				);
				break;
			default:
				call_user_func_array($controller, $route['action'], $actionParams);
				break;
		}
		$controller->Display();
	}

	protected function getRoute($path) {
		$routeParts = [];
		if (isset($this->routes)) {
			foreach ($this->routes as $route) {
				if (!is_array($route) || !isset($route['path']) || !isset($route['controller']) || !isset($route['action'])) {
					throw new RouterException('Routes must be arrays containing path, controller, and action keys.');
				}
				$pattern = '/^'.str_replace('/', '\/', $route['path']).'(\/[A-Za-z0-9\-.]+)*\/*$/';
				if (preg_match($pattern, $path)) {
					if (isset($this->config['controllersNamespace'])) {
						$routeParts['controller'] = '\\'.$this->config['controllersNamespace'].'\\'.$route['controller'];
					} else {
						$routeParts['controller'] = $route['controller'];
					}
					$routeParts['action'] = $route['action'];
					$paramsPath           = substr($path, strlen($route['path']) + 1);
					$routeParts['url']    = array_filter(explode('/', $paramsPath));
				}
			}
		}
		if (empty($routeParts)) {
			if (strlen($path) > 1) { // disregard leading slash
				$pathParts = array_filter(explode('/', $path));
				if (count($pathParts) == 1) {
					$pathParts[] = 'index';
				}
			} else {
				$pathParts = array('main','index');
			}
			if (isset($this->config['controllersNamespace'])) {
				$routeParts['controller'] = '\\'.$this->config['controllersNamespace'].'\\'.ucwords(array_shift($pathParts));
			} else {
				$routeParts['controller'] = ucwords(array_shift($pathParts));
			}
			$routeParts['action'] = ucwords(array_shift($pathParts));
			$routeParts['url']    = $pathParts;
		}

		return $routeParts;
	}
}
