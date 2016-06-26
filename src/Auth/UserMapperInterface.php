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
	public function GetAuthorizedUserForUsernameAndPassword($username, $password);

	/**
	 * Return the user for the given ID. Should be overridden if restrictions should be made on
	 * on how a user should be allowed access.
	 * @param $id
	 * @return mixed
	 */
	public function GetAuthorizedUserById($id);
}
