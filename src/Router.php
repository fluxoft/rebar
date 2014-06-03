<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Exceptions\RouterException;
use Fluxoft\Rebar\Exceptions\AuthenticationException;

/**
 * Router class.
 * 
 * @author Joe Hart
 *
 */
class Router {
	/**
	 * @var array
	 */
	protected $config = array();

	/**
	 * Right now the only supported config parameter is methodArgs, which allows for the setting of a parameter list
	 * to be sent when calling the routed controller method:
	 *
	 * <code>
	 * $config = array(
	 *     'methodArgs' => array('param1', 'param2')
	 * );
	 * $router = new Router($config);
	 * </code>
	 * @param array $config
	 */
	public function __construct(array $config = null) {
		$this->config = $config;
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
	 * @param array $routes
	 * @throws RouterException
	 * @throws AuthenticationException
	 */
	public function Route(array $routes = null) {
		$routeParts = $this->routeParts($routes);
		
		if (class_exists($routeParts['controller'])) {
			/** @var $controllerClass \Fluxoft\Rebar\Controller */
			$controllerClass = new $routeParts['controller']();
		} else {
			throw new RouterException(sprintf('"%s" was not found.', $routeParts['controller']));
		}
		if (!method_exists($controllerClass, $routeParts['method'])) {
			throw new RouterException(sprintf('Could not find a method called %s in %s.', $routeParts['method'], $routeParts['controller']));
		}
		
		if (!$controllerClass->Authenticate($routeParts['method'])) {
			throw new AuthenticationException(sprintf('Authentication failed in %s::%s.', $routeParts['controller'], $routeParts['method']));
		}

		if (isset($this->config['methodArgs'])) {
			$params = $this->config['methodArgs'];
			$params[] = $routeParts['params'];
			call_user_func_array(array($controllerClass, $routeParts['method']), $params);
		} else {
			$controllerClass->$routeParts['method']($routeParts['params']);
		}
		$controllerClass->Display();
	}
	
	protected function routeParts(array $routes = null) {
		$routeParts = array();
		$urlParams = array();
		$request = new Request();
		$path = isset($request['server']['PATH_INFO']) ? $request['server']['PATH_INFO'] : '/main/index';
		if (isset($routes)) {
			foreach ($routes as $route) {
				if (!is_array($route) || !isset($route['path']) || !isset($route['controller']) || !isset($route['method'])) {
					throw new RouterException('Routes must be arrays containing path, controller, and method keys.');
				}
				$pattern = '/^'.str_replace('/', '\/', $route['path']).'(\/[A-Za-z0-9\-.]+)*\/*$/';
				if (preg_match($pattern, $path)) {
					$routeParts['controller'] = $route['controller'];
					$routeParts['method'] = $route['method'];
					$paramsPath = substr($path, strlen($route['path']));
					$urlParams = array_filter(explode('/',$paramsPath));
				}
			}
		}
		if (empty($routeParts)) {
			if (strlen($path)) {
				$pathParts = array_filter(explode('/',$path));
				if (count($pathParts) == 1) {
					$pathParts[] = 'index';
				}
			} else {
				$pathParts = array('default','index');
			}
			$routeParts['controller'] = ucwords(array_shift($pathParts)) . 'Controller';
			$routeParts['method'] = ucwords(array_shift($pathParts));
			$urlParams = $pathParts;
		}
		$routeParts['params']['request'] = $request['request'];
		$routeParts['params']['get'] = $request['get'];
		$routeParts['params']['post'] = $request['post'];
		$routeParts['params']['url'] = $urlParams;
		
		return $routeParts;
	}
}