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
	 * @param string $username
	 * @param string $password
	 * @return UserInterface
	 */
	public function GetAuthorizedUserForUsernameAndPassword(string $username, string $password): UserInterface;

	/**
	 * Return the user for the given ID. Should be overridden if restrictions should be made on
	 * on how a user should be allowed access.
	 * @param mixed $id
	 * @return UserInterface
	 */
	public function GetAuthorizedUserById(mixed $id): UserInterface;
}
