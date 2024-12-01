<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Auth\Reply;
use Fluxoft\Rebar\Http\Request;

class WebAuth extends BaseAuth {
	public function __construct(
		protected UserMapperInterface $userMapper,
		protected TokenManager        $tokenManager,
		protected bool                $useSession = true
	) {
		parent::__construct($userMapper, $tokenManager);
	}

	/**
	 * Get the authenticated user based on the session or refresh token.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request): Reply {
		$authReply = new Reply();
	
		// Prefer to retrieve the AccessToken from cookies or session
		$accessToken = $request->Cookies->Get('AccessToken');
		if ($this->useSession && !isset($accessToken)) {
			$accessToken = $request->Session->Get('AccessToken');
		}
	
		if (isset($accessToken)) {
			try {
				// Decode the AccessToken to get user claims
				$claims = $this->tokenManager->DecodeAccessToken($accessToken);
				$userId = $claims['userId'] ?? null;
	
				if (isset($userId)) {
					$user = $this->userMapper->GetAuthorizedUserById($userId);
					if ($user instanceof UserInterface) {
						$authReply->Auth    = true;
						$authReply->User    = $user;
						$authReply->Token   = $accessToken;
						$authReply->Message = 'Authenticated via access token.';
						$authReply->Claims  = $claims;
						return $authReply;
					}
				}
			} catch (InvalidTokenException $e) {
				unset($accessToken);
				// Access token is invalid or expired, fall back to RefreshToken
			}
		}
	
		// Fall back to RefreshToken
		$refreshToken = $request->Cookies->Get('RefreshToken');
		if ($this->useSession && !isset($refreshToken)) {
			$refreshToken = $request->Session->Get('RefreshToken');
		}
	
		if (isset($refreshToken) && $this->tokenManager->ValidateRefreshToken($refreshToken)) {
			$userId = $this->extractUserIdFromToken($refreshToken);
			if (isset($userId)) {
				$user = $this->userMapper->GetAuthorizedUserById($userId);
				if ($user instanceof UserInterface) {
					// Reissue a new access token
					$accessToken = $this->tokenManager->GenerateAccessToken($user);
					// Decode the AccessToken to get user claims
					$claims = $this->tokenManager->DecodeAccessToken($accessToken);

					// Extend the expiration of the refresh token
					$this->tokenManager->ExtendRefreshTokenExpiration($refreshToken);
	
					$this->persistTokens($request, $user, $accessToken, $refreshToken, false);
	
					$authReply->Auth         = true;
					$authReply->User         = $user;
					$authReply->Token        = $accessToken;
					$authReply->RefreshToken = $refreshToken;
					$authReply->Message      = 'Authenticated via refresh token.';
					$authReply->Claims       = $claims;
					return $authReply;
				}
			}
		}
	
		$authReply->Auth    = false;
		$authReply->Message = 'Authentication failed.';
		return $authReply;
	}
	

	/**
	 * Log the user out by revoking the refresh token.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	public function Logout(Request $request): Reply {
		$authReply = new Reply();

		// Check for a global logout flag
		$globalLogout = (bool) $request->Get('globalLogout', false);

		// Add logic for global logout, or single session logout

		return $authReply;
	}

	/**
	 * Persist tokens in session and cookies for the authenticated user.
	 *
	 * @param Request $request
	 * @param UserInterface $user
	 * @param string $accessToken
	 * @param string $refreshToken
	 * @param bool $remember
	 * @return void
	 */
	protected function persistTokens(
		Request       $request,
		UserInterface $user,
		string        $accessToken,
		string        $refreshToken,
		bool          $remember
	): void {
		$expires = $remember ? strtotime('+30 days') : 0;

		if ($this->useSession) {
			$request->Session->Set('AuthUserId', $user->GetID());
			$request->Session->Set('AccessToken', $accessToken);
			$request->Session->Set('RefreshToken', $refreshToken);
		}

		// Store refresh token in cookies
		$request->Cookies->Set('AccessToken', $accessToken, ['expires' => 0]);
		$request->Cookies->Set('RefreshToken', $refreshToken, ['expires' => $expires]);
	}

	/**
	 * Extract the user ID from the refresh token.
	 *
	 * @param string $token
	 * @return int|null
	 */
	private function extractUserIdFromToken(string $token): ?int {
		try {
			$claims = $this->tokenManager->DecodeAccessToken($token);
			return $claims['userId'] ?? null;
		} catch (\Exception $e) {
			return null;
		}
	}

	private function getToken(Request $request, string $tokenType): ?string {
		if ($this->useSession) {
			$token = $request->Session->Get($tokenType);
			if (isset($token)) {
				return $token;
			}
		}

		$token = $request->Cookies->Get($tokenType);
		return $token ?? null;
	}
}
