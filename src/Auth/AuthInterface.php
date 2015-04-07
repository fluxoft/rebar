<?php
namespace Fluxoft\Rebar\Auth;

interface AuthInterface {
	public function GetAuthenticatedUser();
	public function Login($username, $password, $remember = false);
	public function Logout();
}