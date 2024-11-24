<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Model;

/**
 * Class Error
 * @package Fluxoft\Rebar\Rest
 * @property int Code
 * @property string Message
 * @property mixed Extra
 * @property \Exception Exception
 */
class Error extends Model {
	/**
	 * Error constructor.
	 * @param mixed $code
	 * @param string $message
	 * @param mixed $extra
	 * @param \Exception $exception
	 */
	public function __construct($code, $message = null, $extra = null, \Exception $exception = null) {
		if (is_numeric($code) && isset($message)) {
			parent::__construct([
				'Code' => $code,
				'Message' => $message,
				'Extra' => $extra,
				'Exception' => $exception
			]);
		} else {
			parent::__construct([
				'Message' => $code
			]);
		}
	}

	protected function getException() {
		if (!isset($this->properties['Exception'])) {
			return null;
		} else {
			$e = $this->properties['Exception'];
			return [
				'Code' => $e->getCode(),
				'Message' => $e->getMessage(),
				'Line' => $e->getLine(),
				'File' => $e->getFile(),
				'Trace' => $e->getTraceAsString()
			];
		}
	}
}
