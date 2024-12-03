<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Http\Request;

class ApiAuth extends BaseAuth {
	/**
	 * Get the authenticated user based on the Authorization header.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request): Reply {
		$authReply = new Reply();

		// Retrieve the Authorization header
		$authorizationHeader = $request->Headers->Get('Authorization');
		if (!isset($authorizationHeader) || stripos($authorizationHeader, 'Bearer ') !== 0) {
			$authReply->Auth    = false;
			$authReply->Message = 'Missing or invalid Authorization header.';
			return $authReply;
		}

		// Extract the token
		$token = substr($authorizationHeader, 7); // Skip "Bearer "
		try {
			$claims = $this->tokenManager->DecodeAccessToken($token);
		} catch (InvalidTokenException $e) {
			$authReply->Auth    = false;
			$authReply->Message = $e->getMessage();
			return $authReply;
		}

		// Fetch the user from the claims
		$userId = $claims['userId'] ?? null;
		if (!isset($userId)) {
			$authReply->Auth    = false;
			$authReply->Message = 'Invalid token payload.';
			return $authReply;
		}

		$user = $this->userMapper->GetAuthorizedUserById($userId);
		if (!$user instanceof UserInterface) {
			$authReply->Auth    = false;
			$authReply->Message = 'User not found.';
			return $authReply;
		}

		// Successfully authenticated
		$authReply->Auth        = true;
		$authReply->User        = $user;
		$authReply->AccessToken = $token;
		$authReply->Message     = 'Authenticated via access token.';
		$authReply->Claims      = $claims;
		return $authReply;
	}

	/**
	 * For stateless Logout, we just need to revoke the refresh token.
	 * If a "globalLogout" is set on the Get, we revoke all refresh tokens for the user.
	 */
	public function Logout(Request $request): Reply {
		$authReply = new Reply();

		// Check for a global logout flag
		$globalLogout = (bool) $request->Get->Get('globalLogout', false);

		if ($globalLogout) {
			// Retrieve the Authorization header
			$authorizationHeader = $request->Headers->Get('Authorization');
			if (!isset($authorizationHeader) || stripos($authorizationHeader, 'Bearer ') !== 0) {
				$authReply->Auth    = false;
				$authReply->Message = 'Missing or invalid Authorization header.';
				return $authReply;
			}

			$accessToken = substr($authorizationHeader, 7); // Skip "Bearer "
			try {
				$claims = $this->tokenManager->DecodeAccessToken($accessToken);
			} catch (InvalidTokenException $e) {
				$authReply->Auth    = false;
				$authReply->Message = $e->getMessage();
				return $authReply;
			}

			$userId = $claims['userId'] ?? null;
			if (!isset($userId)) {
				$authReply->Auth    = false;
				$authReply->Message = 'Invalid token payload.';
				return $authReply;
			}

			$this->tokenManager->RevokeRefreshTokensByUserId($userId);

			$authReply->Auth    = true;
			$authReply->Message = 'Logged out globally.';
			return $authReply;
		}

		// Single session logout
		// Check for a refresh token in the cookies:
		$refreshToken = $request->Cookies->Get('RefreshToken');
		if (isset($refreshToken)) {
			$request->Cookies->Delete('RefreshToken');
		} else {
			// Check for a refresh token in the headers
			$refreshToken = $request->Headers->Get('RefreshToken');
		}

		// If we still don't have a refresh token, we can't revoke it
		if (!isset($refreshToken)) {
			$authReply->Auth    = false;
			$authReply->Message = 'Missing RefreshToken header.';
			return $authReply;
		}

		// Attempt to revoke the refresh token
		try {
			$this->tokenManager->RevokeRefreshToken($refreshToken);
			$authReply->Auth    = false;
			$authReply->Message = 'Logged out.';
		} catch (\Exception $e) {
			$authReply->Auth    = false;
			$authReply->Message = 'Failed to log out: ' . $e->getMessage();
		}

		return $authReply;
	}

	protected function persistTokens(
		Request       $request,
		UserInterface $user,
		string        $accessToken,
		string        $refreshToken,
		bool          $remember
	): void {
		// Stateless authentication does not persist tokens.
		unset($request, $user, $accessToken, $refreshToken, $remember);
	}
}
