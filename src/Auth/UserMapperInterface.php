<?php

namespace Fluxoft\Rebar\Auth;

interface UserMapperInterface {
	public function GetOneForUsernameAndPassword($username, $password);
}
