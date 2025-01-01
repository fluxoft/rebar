<?php

namespace Fluxoft\Rebar\Auth;

/**
 * Interface UserInterface
 * Must be implemented by any User class used for authentication
 * @package Fluxoft\Rebar\Auth
 */
interface UserInterface {
	public function GetId(): mixed;
	public function GetAuthUsernameProperty(): string;
	public function IsPasswordValid($password): bool;
}
