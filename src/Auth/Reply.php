<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Model;

/**
 * Class Reply
 * Returned by Auth classes implementations of AuthInterface::Login and
 * AuthInterface::GetAuthenticatedUser
 * @package Fluxoft\rebar\Auth
 * @property bool Auth
 * @property mixed User
 * @property string Token
 * @property string Message
 */
class Reply extends Model {
	public function __construct() {
		parent::__construct([
			'Auth' => false,
			'User' => null,
			'Token' => null,
			'Message' => null
		]);
	}
}
