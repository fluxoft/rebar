<?php

namespace Fluxoft\Rebar\Error\Notifiers;

use Fluxoft\Rebar\Error\NotifierInterface;

class JsonNotifier implements NotifierInterface {
	private bool $verbose;

	/**
	 * @param bool $verbose If true, includes detailed information like stack traces.
	 */
	public function __construct(bool $verbose = false) {
		$this->verbose = $verbose;
	}

	/**
	 * Outputs the exception or error details as JSON.
	 *
	 * @param \Throwable $t
	 * @return void
	 */
	public function Notify(\Throwable $t): void {
		$this->setHeaders();
		$this->echoJson($this->formatException($t));
		$this->callExit();
	}

	/**
	 * Formats the exception or error details into an associative array.
	 *
	 * @param \Throwable $t
	 * @return array
	 */
	private function formatException(\Throwable $t): array {
		$data = [
			'error' => true,
			'message' => $t->getMessage(),
			'code' => $t->getCode(),
			'file' => $t->getFile(),
			'line' => $t->getLine(),
		];

		if ($this->verbose) {
			$data['trace'] = $t->getTrace();
		}

		return $data;
	}

	// @codeCoverageIgnoreStart
	/**
	 * Sends the JSON content type header.
	 *
	 * @return void
	 */
	protected function setHeaders(): void {
		header('Content-Type: application/json');
	}

	/**
	 * Outputs the JSON-encoded data.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function echoJson(array $data): void {
		echo json_encode($data, JSON_PRETTY_PRINT);
	}

	/**
	 * Calls exit to terminate the script.
	 *
	 * @return void
	 */
	protected function callExit(): void {
		exit;
	}
	// @codeCoverageIgnoreEnd
}
