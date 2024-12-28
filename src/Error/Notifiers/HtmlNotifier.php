<?php

namespace Fluxoft\Rebar\Error\Notifiers;

use Fluxoft\Rebar\Error\NotifierInterface;

class HtmlNotifier implements NotifierInterface {
	private bool $verbose;

	public function __construct(bool $verbose = false) {
		$this->verbose = $verbose;
	}

	public function Notify(\Throwable $t): void {
		$this->setHeader();
		$this->outputHtml($this->generateHtml($t));
	}

	private function generateHtml(\Throwable $t): string {
		$html  = "<h1>An error occurred</h1>";
		$html .= "<p>" . htmlspecialchars($t->getMessage()) . "</p>";
		if ($this->verbose) {
			$html .= "<pre>" . htmlspecialchars($t->getTraceAsString()) . "</pre>";
		}
		return $html;
	}

	// @codeCoverageIgnoreStart
	protected function setHeader(): void {
		header('Content-Type: text/html');
	}

	protected function outputHtml(string $html): void {
		echo $html;
	}
	// @codeCoverageIgnoreEnd
}