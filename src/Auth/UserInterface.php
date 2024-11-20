<?php

namespace Fluxoft\Rebar\Auth;

/**
 * Interface UserInterface
 * Must be implemented by any User class used for authentication
 * @package Fluxoft\Rebar\Auth
 */
interface UserInterface {
	/**
	 * Check the given password to see if it matches the User's Password.
	 * @param $password
	 * @return bool
	 */
	public function IsPasswordValid($password);
}
