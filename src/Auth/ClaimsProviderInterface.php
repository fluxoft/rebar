<?php

namespace Fluxoft\Rebar\Auth;

interface ClaimsProviderInterface {
	public function GetClaims(UserInterface $user): array;
}
