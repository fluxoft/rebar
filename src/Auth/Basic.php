<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Http\Request;

/**
 * Class Basic
 * @package Fluxoft\Rebar\Auth
 */
class Basic implements AuthInterface {
	protected $userMapper;
	protected $realm;
	protected $message;

	public function __construct(UserMapperInterface $userMapper, $realm, $message) {
		$this->userMapper = $userMapper;
		$this->realm      = $realm;
		$this->message    = $message;
	}

	/**
	 * Attempt to return a Reply for the authenticated user.
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request) {
		$basicAuthUser = $request->Server('PHP_AUTH_USER');

		if (!isset($basicAuthUser)) {
			$this->sendChallenge($this->realm, $this->message);
			return null;
		} else {
			return $this->Login($basicAuthUser, $request->Server('PHP_AUTH_PW', ''));
		}
	}

	/**
	 * @param $realm
	 * @param $message
	 * @codeCoverageIgnore
	 */
	protected function sendChallenge($realm, $message) {
		header('WWW-Authenticate: Basic realm="'.$realm.'"');
		header('HTTP/1.0 401 Unauthorized');
		echo $message;
		exit;
	}



	/**
	 * Attempt to log the user in using the given $username and $password
	 * and return a Reply object.
	 * @param string $username
	 * @param string $password
	 * @param bool $remember
	 * @return \Fluxoft\Rebar\Auth\Reply
	 */
	public function Login($username, $password, $remember = false) {
		// unused in this implementation
		unset($remember);
		
		$reply       = new Reply();
		$user        = $this->userMapper->GetAuthorizedUserForUsernameAndPassword($username, $password);
		$reply->Auth = true;
		$reply->User = $user;
		return $reply;
	}

	/**
	 * Log the user out and return a blank Reply
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function Logout(Request $request) {
		//unused
		unset($request);

		// You can't really log out of a basic authentication session, since the browser will
		// just keep sending the same Authorization header over and over, so this method won't
		// really do anything, but return a blank Reply for conformity's sake
		return new Reply();
	}
}
