<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Model;

/**
 * Class Error
 * @package Fluxoft\Rebar\Rest
 * @property int Code
 * @property string Message
 * @property mixed Extra
 */
class Error extends Model {
	protected $properties = [
		'Code' => 0,
		'Message' => '',
		'Extra' => ''
	];

	/**
	 * Error constructor.
	 * @param mixed $code
	 * @param string $message
	 * @param mixed $extra
	 */
	public function __construct($code, $message = null, $extra = null) {
		if (is_numeric($code) && isset($message)) {
			parent::__construct([
				'Code' => $code,
				'Message' => $message,
				'Extra' => $extra
			]);
		} else {
			parent::__construct([
				'Message' => $code
			]);
		}
	}
}
