<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Model;

/**
 * Class RefreshToken
 * @package Fluxoft\Rebar\Auth
 * @property int Id
 * @property int UserId
 * @property int SeriesId
 * @property string Token
 * @property string ExpiresAt
 * @property string CreatedAt
 * @property string RevokedAt
 */
class RefreshToken extends Model {
	protected static array $defaultProperties = [
		'Id'        => 0,
		'UserId'    => 0,
		'SeriesId'  => 0,
		'Token'     => null,
		'ExpiresAt' => null,
		'CreatedAt' => null,
		'RevokedAt' => null
	];
}
