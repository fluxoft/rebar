<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Http\Request;

class Jwt implements AuthInterface {
	/** @var Request */
	protected $request;

	public function __construct (Request $request) {
		$this->request = $request;
	}

	public function GetAuthenticatedUser() {
		/*
		 * Try to get the token first from the Authorization header, if present.
		 * If not found there, try to find one in the querystring as "token".
		 */
		// TODO: Implement GetAuthenticatedUser() method.
	}

	public function Login($username, $password) {
		// TODO: Implement Login() method.
	}

	public function Logout() {
		/*
		 * There isn't a way to really "log out" someone who's using JWT auth. Either their
		 * token was valid or it wasn't, so this just returns true.
		 */
		return true;
	}
}
