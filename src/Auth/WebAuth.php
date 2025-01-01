<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Auth\Reply;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class WebAuth extends BaseAuth {
	public function __construct(
		protected UserMapperInterface $userMapper,
		protected TokenManager        $tokenManager,
		protected bool                $useSession = false,
		protected string              $loginPath = '/auth/login'
	) {
		parent::__construct($userMapper, $tokenManager);
	}

	public function HandleAuthFailure(Request $request, Response $response): void {
		$path        = $request->Path;
		$queryString = $request->Server('QUERY_STRING', '');
		$redirectUrl = $path . ($queryString ? '?' . $queryString : '');
		$loginUrl    = $this->loginPath . ($redirectUrl ? '?redirect=' . urlencode($redirectUrl) : '');
		$response->Redirect($loginUrl);
	}

	/**
	 * Get the authenticated user based on the session or refresh token.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request): Reply {
		$authReply = new Reply();

		// Retrieve the AccessToken
		$accessToken = $this->getToken($request, 'AccessToken');

		if (isset($accessToken)) {
			try {
				// Decode the AccessToken to get user claims
				$claims = $this->tokenManager->DecodeAccessToken($accessToken);
				$userId = $claims['userId'] ?? null;

				if (isset($userId)) {
					$user = $this->userMapper->GetAuthorizedUserById($userId);
					if ($user instanceof UserInterface) {
						$authReply->Auth        = true;
						$authReply->User        = $user;
						$authReply->AccessToken = $accessToken;
						$authReply->Message     = 'Authenticated via access token.';
						$authReply->Claims      = $claims;
						return $authReply;
					}
				}
				// If claims or validation fails
				$authReply->Message = 'Invalid claims.'; // Do not reveal specifics
			} catch (InvalidTokenException $e) {
				unset($accessToken);
				$authReply->Message = 'Invalid or expired token';
			} catch (\Exception $e) {
				$authReply->Message = 'Unexpected error'; // Set message for unexpected error
			}
		} else {
			$authReply->Message = 'No token provided'; // Set message for no token
		}

		// Fall back to RefreshToken
		$refreshToken = $this->getToken($request, 'RefreshToken');

		if (isset($refreshToken)) {
			if ($this->tokenManager->ValidateRefreshToken($refreshToken)) {
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
						$authReply->AccessToken  = $accessToken;
						$authReply->RefreshToken = $refreshToken;
						$authReply->Message      = 'Authenticated via refresh token.';
						$authReply->Claims       = $claims;
						return $authReply;
					}
				}
				$authReply->Message = 'Authentication failed.'; // Do not reveal specifics
			} else {
				$authReply->Message = 'Invalid refresh token';
			}
		}

		$authReply->Auth = false;
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

		// Attempt to retrieve the RefreshToken
		$refreshToken = $this->getToken($request, 'RefreshToken');

		if (isset($refreshToken)) {
			// Extract userId from the refresh token to revoke all tokens for global logout
			$userId = $this->extractUserIdFromToken($refreshToken);
			if ($globalLogout && isset($userId)) {
				$this->tokenManager->RevokeRefreshTokensByUserId($userId);
			} elseif (!$globalLogout) {
				$this->tokenManager->RevokeRefreshToken($refreshToken);
			}
		}

		if ($this->useSession) {
			// Delete session tokens
			$request->Session->Delete('AccessToken');
			$request->Session->Delete('RefreshToken');
		}

		// Delete cookies
		$request->Cookies->Delete('AccessToken');
		$request->Cookies->Delete('RefreshToken');

		$authReply->Auth    = false;
		$authReply->Message = $globalLogout
			? 'User logged out from all devices.'
			: 'User logged out from this session.';

		return $authReply;
	}

	/**
	 * Persist tokens in session and cookies for the authenticated user.
	 *
	 * @param Request       $request
	 * @param UserInterface $user
	 * @param string        $accessToken
	 * @param string        $refreshToken
	 * @param bool          $remember
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
		} catch (InvalidTokenException $e) {
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
