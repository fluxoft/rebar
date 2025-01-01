<?php

namespace Fluxoft\Rebar\Auth\Exceptions;

class BasicAuthChallengeException extends \Exception {
	protected string $realm;

	public function __construct(string $realm, string $message) {
		$this->realm = $realm;
		parent::__construct($message, 401);
	}

	public function getRealm(): string {
		return $this->realm;
	}
}
