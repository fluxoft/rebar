<?php

namespace Fluxoft\Rebar;

/**
 * Class Route
 * @package Fluxoft\Rebar
 * @property string Path
 * @property string Controller
 * @property string Action
 */
class Route extends Model {
	protected $properties = [
		'Path' => '/',
		'Controller' => 'Main',
		'Action' => 'Index'
	];

	/**
	 * @param array $path
	 * @param $controller
	 * @param $action
	 */
	public function __construct($path, $controller, $action) {
		$this->Path       = $path;
		$this->Controller = $controller;
		$this->Action     = $action;
	}
}
