<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {
	/**
	 * @dataProvider routeProvider
	 * @param string $path
	 * @param string $controller
	 * @param string $action
	 */
	public function testNewRoute(string $path, string $controller, string $action) {
		$route = new Route($path, $controller, $action);
		$this->assertEquals($path, $route->Path);
		$this->assertEquals($controller, $route->Controller);
		$this->assertEquals($action, $route->Action);
	}
	public function routeProvider() {
		return [
			[
				'path' => '/',
				'controller' => 'Main',
				'action' => 'Index'
			],
			[
				'path' => '/sitemap.xml',
				'controller' => 'Main',
				'action' => 'Sitemap'
			],
		];
	}
}
