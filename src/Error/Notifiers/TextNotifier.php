<?php

namespace Fluxoft\Rebar\Error\Notifiers;

use Fluxoft\Rebar\Error\NotifierInterface;

/**
 * Outputs unhandled exceptions as plain text. Typically used for debugging or CLI environments.
 * Class TextNotifier
 * @package Fluxoft\Rebar\Error
 */
class TextNotifier implements NotifierInterface {
	private bool $verbose = true;

	/**
	 * @param bool $verbose Whether to include detailed stack trace and exception details.
	 */
	public function __construct(bool $verbose = true) {
		$this->verbose = $verbose;
	}

	public function Notify(\Throwable $t): void {
		$this->setHeaders();
		$this->echoErrorText($this->getErrorText($t));
		$this->callExit();
	}

	protected function getErrorText(\Throwable $throwable): string {
		$text  = "******************************\n";
		$text .= "***  Unhandled exception:  ***\n";
		$text .= "******************************\n";
		$text .= "\n";

		if ($this->verbose) {
			$text .= (string) $throwable;
		} else {
			$text .= "A critical error occurred. Please contact the system administrator.\n";
		}

		return $text;
	}

	// @codeCoverageIgnoreStart
	protected function setHeaders(): void {
		if (php_sapi_name() !== 'cli') {
			header('HTTP/1.1 500 Internal Server Error');
			header('Content-Type: text/plain');
		}
	}
	protected function echoErrorText(string $text): void {
		echo $text;
	}
	protected function callExit(): void {
		exit;
	}
	// @codeCoverageIgnoreEnd
}
