<?php

namespace Fluxoft\Rebar\Error;

/**
 * Class ErrorHandler
 * @package Fluxoft\Rebar\Error
 * @codeCoverageIgnore
 */
class ErrorHandler {
	private static array $notifiers = [];

	public static function Register(array $notifiers): void {
		self::$notifiers = $notifiers;
		self::registerHandlers();
	}

	public static function HandleError(int $severity, string $message, string $filename, int $lineno): void {
		$error = new \ErrorException($message, 0, $severity, $filename, $lineno);
		self::notifyAll($error);
		throw $error; // Rethrow for further handling.
	}

	public static function HandleException(\Throwable $t): void {
		self::notifyAll($t);
	}

	public static function HandleShutdown(): void {
		$error = error_get_last();
		if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
			$errorException = new \ErrorException(
				$error['message'],
				0,
				$error['type'],
				$error['file'],
				$error['line']
			);
			self::notifyAll($errorException);
		}
	}

	protected static function registerHandlers(): void {
		set_error_handler([self::class, 'HandleError']);
		set_exception_handler([self::class, 'HandleException']);
		register_shutdown_function([self::class, 'HandleShutdown']);
	}

	/**
	 * Notify all registered notifiers about the exception or error.
	 *
	 * @param \Throwable $t
	 */
	private static function notifyAll(\Throwable $t): void {
		foreach (self::$notifiers as $notifier) {
			$notifier->Notify($t);
		}
	}
}
