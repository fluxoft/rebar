<?php

namespace Fluxoft\Rebar\Error\Notifiers;

use Fluxoft\Rebar\Error\NotifierInterface;
use Psr\Log\LoggerInterface;

class LoggerNotifier implements NotifierInterface {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function Notify(\Throwable $t): void {
		try {
			$this->logger->error($t->getMessage(), ['exception' => $t]);
		} catch (\Throwable $t) {
			unset($t); // If the logger fails, we can't do anything about it.
		}
	}
}
