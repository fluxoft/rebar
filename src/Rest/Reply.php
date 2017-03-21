<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Model;

/**
 * Class Reply
 * @package Fluxoft\rebar\src\Rest
 * @property int Status
 * @property mixed Data
 * @property array Meta
 * @property Error Error
 */
class Reply extends Model {
	protected $properties = [
		'Status' => 200,
		'Data' => [],
		'Meta' => [],
		'Error' => null
	];
	public function __construct($status = 200, $data = [], $meta = [], $error = null) {
		parent::__construct([
			'Status' => $status,
			'Data' => $data,
			'Meta' => $meta,
			'Error' => $error
		]);
	}
}
