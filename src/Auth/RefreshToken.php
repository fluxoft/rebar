<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Model;

/**
 * Class RefreshToken
 * A model for a refresh token.
 * @property int $Id
 * @property int $UserId
 * @property string $SeriesId
 * @property string $Token
 * @property string $ExpiresAt
 * @property string $CreatedAt
 * @property string $RevokedAt
 */
class RefreshToken extends Model {
	protected static array $defaultProperties = [
		'Id'        => 0,
		'UserId'    => 0,
		'SeriesId'  => '',
		'Token'     => null,
		'ExpiresAt' => null,
		'CreatedAt' => null,
		'RevokedAt' => null
	];
}
