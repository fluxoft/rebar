<?php
namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Http\Request;

/**
 * Interface AuthInterface
 * Must be implemented by any class used for authentication
 * @package Fluxoft\Rebar\Auth
 */
interface AuthInterface {
	/**
	 * Attempt to return a Reply for the authenticated user.
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply|null
	 */
	public function GetAuthenticatedUser(Request $request): ?Reply;

	/**
	 * Attempt to log the user in using the given $username and $password
	 * and return a Reply object.
	 * @param string $username
	 * @param string $password
	 * @param bool $remember
	 * @return Reply
	 */
	public function Login($username, $password, $remember): Reply;

	/**
	 * Log the user out and return a blank Reply
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function Logout(Request $request): Reply;
}
