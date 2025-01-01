<?php

namespace Fluxoft\Rebar\Auth;

interface RefreshTokenMapperInterface {
	public function GetRefreshToken(
		mixed $userId,
		?int $seriesId = null,
		?string $token = null,
		bool $validOnly = false
	): ?RefreshToken;

	/**
	 * @param mixed $userId
	 * @return RefreshToken[]
	 */
	public function GetRefreshTokensByUserId(mixed $userId): array;

	public function SaveRefreshToken(RefreshToken $refreshToken): void;
	public function RevokeRefreshToken(RefreshToken $refreshToken): void;
}
