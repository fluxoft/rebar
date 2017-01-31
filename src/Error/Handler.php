<?php

namespace Fluxoft\Rebar\Error;

class Handler {
	/**
	 * @var NotifierInterface
	 */
	static protected $notifier;

	/**
	 * @param NotifierInterface $notifier A class implementing NotifierInterface can be included
	 * to send custom notifications when an exception is handled.
	 */
	public static function Handle(NotifierInterface $notifier) {
		self::$notifier = $notifier;

		set_error_handler(
			[__CLASS__, 'ErrorHandler']
		);
		set_exception_handler(
			[__CLASS__, 'ExceptionHandler']
		);
	}

	public static function ErrorHandler($severity, $message, $filename, $lineno) {
		throw new \ErrorException($message, 0, $severity, $filename, $lineno);
	}
	public static function ExceptionHandler(\Throwable $t) {
		self::$notifier->Notify($t);
	}
}
