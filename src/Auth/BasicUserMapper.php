<?php

namespace Fluxoft\Rebar\Auth;

abstract class BasicUserMapper implements UserMapperInterface {
	protected $users = [];

	public function GetOneForUsernameAndPassword($username, $password) {
		if (isset($this->users[$username]) && $this->users[$username] === $password) {
			return ['username' => $username, 'password' => $password];
		}
		return false;
	}
}
