<?php

namespace Fluxoft\Rebar\Auth;

class Basic implements AuthInterface {
	protected $userMapper;
	protected $realm;
	protected $message;

	protected $authenticatedUser = false;

	public function __construct(UserMapperInterface $userMapper, $realm, $message) {
		$this->userMapper = $userMapper;
		$this->realm      = $realm;
		$this->message    = $message;
	}

	public function GetAuthenticatedUser() {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
			header('HTTP/1.0 401 Unauthorized');
			echo $this->message;
			exit;
		} else {
			$this->authenticatedUser = $this->Login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
		return $this->authenticatedUser;
	}

	public function Login($username, $password) {
		return $this->userMapper->GetOneForUsernameAndPassword($username, $password);
	}

	public function Logout() {
		// You can't really log out of a basic authentication session, since the browser will
		// just keep sending the same Authorization header over and over, so this method won't
		// really do anything.
		return true;
	}
}
