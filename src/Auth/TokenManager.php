<?php

namespace Fluxoft\Rebar\Auth;

use Firebase\JWT\JWT;
use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Utils;

class TokenManager {
	public function __construct(
		private RefreshTokenMapperInterface $refreshTokenMapper,
		private ClaimsProviderInterface     $claimsProvider,
		private string                      $secretKey,
		private \DateInterval               $accessExpires,
		private \DateInterval               $refreshExpires,
		// Default to a grace period of 15 minutes for refresh tokens to still be valid after expiration
		private \DateInterval               $gracePeriod = new \DateInterval('PT15M')
	) {}

	public function GenerateAccessToken(UserInterface $user) : string {
		$now     = new \DateTimeImmutable();
		$claims  = $this->claimsProvider->GetClaims($user);
		$payload = array_merge($claims, [
			'iat' => $now->getTimestamp(),
			'exp' => $now->add($this->accessExpires)->getTimestamp()
		]);

		return JWT::encode($payload, $this->secretKey);
	}

	// Generate a simpler RefreshToken
	public function GenerateRefreshToken(UserInterface $user) : string {
		$token = base64_encode(implode('|', [
			$user->GetId(),
			uniqid('', true),
			Utils::GetUUID()
		]));
		return $token;
	}

	public function StoreRefreshToken(string $refreshToken) : void {
		$tokenParts = explode('|', base64_decode($refreshToken));
		$userId     = $tokenParts[0];
		$seriesId   = $tokenParts[1];
		$token      = $tokenParts[2];

		$refreshToken            = new RefreshToken();
		$refreshToken->UserId    = $userId;
		$refreshToken->SeriesId  = $seriesId;
		$refreshToken->Token     = $token;
		$refreshToken->ExpiresAt = (new \DateTimeImmutable())->add($this->refreshExpires)->format('Y-m-d H:i:s');
		$refreshToken->CreatedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
		$refreshToken->RevokedAt = null;

		$this->refreshTokenMapper->SaveRefreshToken($refreshToken);
	}

	public function ValidateRefreshToken(string $refreshTokenString) : bool {
		[$userId, $seriesId, $token] = $this->decodeRefreshTokenString($refreshTokenString);
		$refreshToken                = $this->refreshTokenMapper->GetRefreshToken($userId, $seriesId, $token, true);
		
		if ($refreshToken instanceof RefreshToken) {
			$expiresAt = new \DateTimeImmutable($refreshToken->ExpiresAt);
			$now       = new \DateTimeImmutable();

			// Token is valid if it hasn't expired or is within the grace period
			if ($now < $expiresAt || $now < $expiresAt->add($this->gracePeriod)) {
				return true;
			}
		}
		return false;
	}

	public function RevokeRefreshToken(string $refreshTokenString) : void {
		[$userId, $seriesId, $token] = $this->decodeRefreshTokenString($refreshTokenString);
		$refreshToken                = $this->refreshTokenMapper->GetRefreshToken($userId, $seriesId, $token, false);
		
		if ($refreshToken instanceof RefreshToken) {
			$refreshToken->RevokedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
			$refreshToken->ExpiresAt = $refreshToken->RevokedAt; // Also expire the token
			$this->refreshTokenMapper->RevokeRefreshToken($refreshToken);
		}
	}

	public function RevokeRefreshTokensByUserId(mixed $userId): void {
		$refreshTokens = $this->refreshTokenMapper->GetRefreshTokensByUserId($userId);
		foreach ($refreshTokens as $refreshToken) {
			$this->RevokeRefreshToken(
				$this->encodeRefreshTokenString($refreshToken->UserId, $refreshToken->SeriesId, $refreshToken->Token)
			);
		}
	}

	public function ExtendRefreshTokenExpiration(string $refreshTokenString) : void {
		[$userId, $seriesId, $token] = $this->decodeRefreshTokenString($refreshTokenString);
		$refreshToken                = $this->refreshTokenMapper->GetRefreshToken($userId, $seriesId, $token, false);

		if ($refreshToken instanceof RefreshToken) {
			$refreshToken->ExpiresAt = (new \DateTimeImmutable())->add($this->refreshExpires)->format('Y-m-d H:i:s');
			$this->refreshTokenMapper->SaveRefreshToken($refreshToken);
		}
	}

	protected function encodeRefreshTokenString(mixed $userId, int $seriesId, string $token) : string {
		return base64_encode(implode('|', [$userId, $seriesId, $token]));
	}
	protected function decodeRefreshTokenString(string $refreshTokenString) : array {
		$tokenParts = explode('|', base64_decode($refreshTokenString));
		if (count($tokenParts) !== 3) {
			throw new InvalidTokenException('Invalid refresh token format');
		}
		return $tokenParts;
	}

	public function DecodeAccessToken(string $accessToken) : array {
		try {
			return (array) JWT::decode($accessToken, $this->secretKey, ['HS256']);
		} catch (\Exception $e) {
			throw new InvalidTokenException('Invalid or expired token', 0, $e);
		}
	}
}
