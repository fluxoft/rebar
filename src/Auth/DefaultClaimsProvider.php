<?php

namespace Fluxoft\Rebar\Auth;

/**
 * Class DefaultClaimsProvider
 * Provides the default claims for a user. Should be overridden if you need to provide
 * additional claims other than just the user ID. For instance, you might want to provide
 * the user's email address, or their roles and permissions.
 * @package Fluxoft\Rebar\Auth
 */
class DefaultClaimsProvider implements ClaimsProviderInterface {
	public function GetClaims(UserInterface $user): array {
		return [
			'userId' => $user->GetId()
		];
	}
}
