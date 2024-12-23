<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Model;

/**
 * Class Route
 * @package Fluxoft\Rebar
 * @property string $Path
 * @property string $Controller
 * @property string $Action
 */
class Route extends Model {
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
