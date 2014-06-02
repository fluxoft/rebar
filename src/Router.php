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
	 * @param Container $c
	 * @param array $routes
	 * @throws RouterException
	 * @throws AuthenticationException
	 */
	public static function Route(Container $c, array $routes = null) {
		$routeParts = static::routeParts($routes);
		
		if (class_exists($routeParts['controller'])) {
			/** @var $controllerClass \Fluxoft\Rebar\Controller */
			$controllerClass = new $routeParts['controller']($c);
		} else {
			throw new RouterException(sprintf('"%s" was not found.', $routeParts['controller']));
		}
		if (!method_exists($controllerClass, $routeParts['method'])) {
			throw new RouterException(sprintf('Could not find a method called %s in %s.', $routeParts['method'], $routeParts['controller']));
		}
		
		if (!$controllerClass->Authenticate($routeParts['method'])) {
			throw new AuthenticationException(sprintf('Authentication failed in %s::%s.', $routeParts['controller'], $routeParts['method']));
		}
		
		$controllerClass->$routeParts['method']($routeParts['params']);
		$controllerClass->Display();
	}
	
	protected static function routeParts(array $routes) {
		$routeParts = array();
		$urlParams = array();
		$request = new Request();
		$path = isset($request['server']['PATH_INFO']) ? $request['server']['PATH_INFO'] : '/main/default';
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
					$pathParts[] = 'default';
				}
			} else {
				$pathParts = array('main','default');
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
