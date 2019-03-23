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
	 * @param string $path
	 * @param string $controller
	 * @param string $action
	 */
	public function __construct($path, $controller, $action) {
		parent::__construct([
			'Path' => $path,
			'Controller' => $controller,
			'Action' => $action
		]);
	}
}
