<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Model;

/**
 * Class Reply
 * @package Fluxoft\rebar\src\Rest
 * @property int Status
 * @property array Data
 */
class Reply extends Model {
	protected $properties = [
		'Status' => 200,
		'Data' => []
	];
	public function __construct($status = 200, array $data = []) {
		$this->Status = $status;
		$this->Data   = $data;
	}
}
