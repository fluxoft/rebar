<?php
namespace Fluxoft\Rebar\Auth;

interface AuthInterface {
	public function Login($username, $password, $remember);
	public function Logout();
	public function AutoLogin();
	public function IsLoggedIn();
} 