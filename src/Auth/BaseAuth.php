<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Http\Request;

abstract class BaseAuth implements AuthInterface {
	public function __construct(
		protected UserMapperInterface $userMapper,
		protected TokenManager $tokenManager
	) {}

	/**
	 * Persist tokens for the authenticated user.
	 * WebAuth may use cookies, while ApiAuth might not persist anything.
	 */
	abstract protected function persistTokens(
		Request       $request,
		UserInterface $user,
		string        $accessToken,
		string        $refreshToken,
		bool          $remember
	): void;

	/**
	 * Log the user in using the provided username and password.
	 * Returns a Reply object containing authentication details.
	 *
	 * @param Request $request The current request object.
	 * @param string $username The username for login.
	 * @param string $password The password for login.
	 * @param bool $remember Whether to persist the token for a longer duration.
	 * @return Reply
	 * @throws InvalidCredentialsException
	 */
	public function Login(Request $request, string $username, string $password, bool $remember = false): Reply {
		$user = $this->userMapper->GetAuthorizedUserForUsernameAndPassword($username, $password);

		$accessToken  = $this->tokenManager->GenerateAccessToken($user);
		$refreshToken = $this->tokenManager->GenerateRefreshToken($user);

		// Decode the access token to get the claims
		$claims = $this->tokenManager->DecodeAccessToken($accessToken);

		// Store refresh token for revocation or other backend purposes
		$this->tokenManager->StoreRefreshToken($refreshToken);

		// Persist tokens using subclass-specific logic
		$this->persistTokens($request, $user, $accessToken, $refreshToken, $remember);

		$reply               = new Reply();
		$reply->Auth         = true;
		$reply->User         = $user;
		$reply->AccessToken  = $accessToken;
		$reply->RefreshToken = $refreshToken;
		$reply->Message      = 'Authenticated via username and password.';
		$reply->Claims       = $claims;

		return $reply;
	}


	/**
	 * Logout logic for specific auth implementations can override this as needed.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	abstract public function Logout(Request $request): Reply;

	/**
	 * Abstract method for retrieving the authenticated user.
	 * Must be implemented by WebAuth and ApiAuth classes.
	 *
	 * @param Request $request
	 * @return Reply
	 */
	abstract public function GetAuthenticatedUser(Request $request): Reply;
}
