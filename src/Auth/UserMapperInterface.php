<?php

namespace Fluxoft\Rebar\Auth;

/**
 * Interface UserMapperInterface
 * Must be implemented by any UserMapper class used for authentication.
 * @package Fluxoft\Rebar\Auth
 */
interface UserMapperInterface {
	/**
	 * Return the user for the given username and password.
	 * @param $username
	 * @param $password
	 * @return mixed
	 */
	public function GetOneForUsernameAndPassword($username, $password);

	/**
	 * Return the user for the given ID
	 * @param $id
	 * @return mixed
	 */
	public function GetOneById($id);
}
