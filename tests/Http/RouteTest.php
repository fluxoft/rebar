<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Http\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {
	/** @var Route */
	protected $route;

 protected function setup(): void {
	 $this->route = new Route('/', 'Main', 'Index');
 }

 public function testGetPath() {
	 $this->assertEquals('/', $this->route->Path);
 }

 public function testGetController() {
	 $this->assertEquals('Main', $this->route->Controller);
 }

 public function testGetAction() {
	 $this->assertEquals('Index', $this->route->Action);
 }

	// Additional tests can be added here
}
