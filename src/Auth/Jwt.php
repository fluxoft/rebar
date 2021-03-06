<?php

namespace Fluxoft\Rebar\Auth;

use Firebase\JWT\ExpiredException;
use Fluxoft\Rebar\Http\Request;

/**
 * Class Jwt
 * @package Fluxoft\Rebar\Auth
 */
class Jwt implements AuthInterface {
	/** @var UserMapperInterface */
	protected $userMapper;
	/** @var string */
	protected $secretKey;
	/** @var \DateInterval */
	protected $expires;

	/** @var Reply */
	private $auth = null;

	/**
	 * @param UserMapperInterface $userMapper
	 * @param string $secretKey
	 * @param \DateInterval $expires Seconds to expiration
	 */
	public function __construct(
		UserMapperInterface $userMapper,
		$secretKey,
		\DateInterval       $expires = null
	) {
		$this->userMapper = $userMapper;
		$this->secretKey  = $secretKey;

		if (!isset($expires)) {
			$expires = new \DateInterval('P30D');
		}
		$this->expires = $expires;
	}

	/**
	 * Attempt to return a Reply for the authenticated user.
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request) {
		if (!isset($this->auth)) {
			$auth     = new Reply();
			$authUser = null;
			// Check that valid tokens are set
			$validToken = $this->getValidTokenPayload($request);
			if (!isset($validToken)) {
				$auth->Message = 'No valid AuthToken found in Request.';
			} else {
				if ($validToken === 'expired') {
					$auth->Message = 'The token is expired.';
				} else {
					// a valid token was found - use it to pull the correct user
					$authUser = $this->userMapper->GetAuthorizedUserById($validToken->userId);
					if ($authUser instanceof UserInterface) {
						$tokenString   = $this->getTokenString($authUser);
						$auth->Auth    = true;
						$auth->Token   = $tokenString;
						$auth->Message = 'Found valid token and logged in';
					} else {
						$auth->Message = 'Tried to log in using token but user not found.';
					}
				}
			}
			$auth->User = $authUser;
			$this->auth = $auth;
		}
		return $this->auth;
	}

	/**
	 * Attempt to log the user in using the given $username and $password
	 * and return a Reply object.
	 * @param string $username
	 * @param string $password
	 * @param bool $remember
	 * @return \Fluxoft\Rebar\Auth\Reply
	 */
	public function Login($username, $password, $remember = null) {
		// unused in this implementation
		$remember = null;

		$reply = new Reply();
		$user  = $this->userMapper->GetAuthorizedUserForUsernameAndPassword($username, $password);
		if ($user instanceof UserInterface) {
			$tokenString  = $this->getTokenString($user);
			$reply->Auth  = true;
			$reply->User  = $user;
			$reply->Token = $tokenString;
			$this->auth   = $reply;
		} else {
			$reply->Message = 'User not found';
		}
		return $reply;
	}

	/**
	 * Log the user out and return a blank Reply
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function Logout(Request $request) {
		unset($request);

		// You can't really log out of a JWT authentication session, since the same token
		// could be sent later on and will be accepted for as long as it is valid, so this
		// method won't really do anything, but return a blank Reply for conformity's sake
		return new Reply();
	}

	protected function getTokenString(UserInterface $user) {
		$now               = new \DateTime('now', new \DateTimeZone('UTC'));
		$payload           = [];
		$payload['userId'] = $user->GetID();
		$payload['iat']    = $now->format('U');
		$payload['exp']    = $now->add($this->expires)->format('U');

		return $this->callFirebaseEncode($payload);
	}
	protected function getValidTokenPayload(Request $request) {
		// try to get a token first from the Authorization header, then from the GET and POST vars
		$authorization = $request->Headers('Authorization');
		$getToken      = $request->Get('AuthToken');
		$postToken     = $request->Post('AuthToken');
		if (isset($authorization) && substr($authorization, 0, 7) === 'Bearer ') {
			$tokenString = substr($authorization, 7);

			// next 2 conditions ignored because xdebug doesn't like "elseif"
		} elseif (isset($getToken)) { // @codeCoverageIgnore
			$tokenString = $getToken;
		} elseif (isset($postToken)) { // @codeCoverageIgnore
			$tokenString = $postToken;
		} else {
			$tokenString = null;
		}

		if (isset($tokenString)) {
			try {
				return $this->callFirebaseDecode($tokenString);
			} catch (ExpiredException $e) {
				return 'expired';
			}
		} else {
			return null;
		}
	}

	/**
	 * @param $payload
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function callFirebaseEncode($payload) {
		return \Firebase\JWT\JWT::encode($payload, $this->secretKey, 'HS256');
	}

	/**
	 * @param $tokenString
	 * @return object
	 * @codeCoverageIgnore
	 */
	protected function callFirebaseDecode($tokenString) {
		return \Firebase\JWT\JWT::decode($tokenString, $this->secretKey, ['HS256']);
	}
}
