<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Model;

/**
 * Class Reply
 * Returned by Auth classes implementations of AuthInterface::Login and
 * AuthInterface::GetAuthenticatedUser
 * @package Fluxoft\Rebar\Auth
 * @property bool   $Auth True if the user is authenticated
 * @property mixed  $User
 * @property string $AccessToken
 * @property string $RefreshToken
 * @property string $Message
 * @property array  $Claims User claims fro the access token
 */
class Reply extends Model {
	public function __construct() {
		parent::__construct([
			'Auth' => false,
			'User' => null,
			'AccessToken' => null,
			'RefreshToken' => null,
			'Message' => null,
			'Claims' => []
		]);
	}
}
