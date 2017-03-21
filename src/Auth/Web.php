<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Db\Token;
use Fluxoft\rebar\Auth\Db\TokenMapper;
use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Session;

/**
 * Class Web
 * @package Fluxoft\Rebar\Auth
 */
class Web implements AuthInterface {
	/** @var UserMapperInterface */
	protected $userMapper;
	/** @var TokenMapper */
	protected $tokenMapper;
	/** @var Cookies */
	protected $cookies;
	/** @var Session */
	protected $session;
	/** @var int */
	protected $expiresDays;

	/** @var Reply */
	private $auth = null;

	/**
	 * @param UserMapperInterface $userMapper
	 * @param TokenMapper $tokenMapper
	 * @param Cookies $cookies
	 * @param Session $session
	 * @param int $expiresDays
	 */
	public function __construct(
		UserMapperInterface $userMapper,
		TokenMapper $tokenMapper,
		Cookies $cookies,
		Session $session,
		$expiresDays = 30
	) {
		$this->userMapper  = $userMapper;
		$this->tokenMapper = $tokenMapper;
		$this->cookies     = $cookies;
		$this->session     = $session;
		$this->expiresDays = $expiresDays;
	}

	/**
	 * Attempt to return a Reply for the authenticated user.
	 * @param Request $request
	 * @return Reply
	 */
	public function GetAuthenticatedUser(Request $request) {
		if (!isset($this->auth)) {
			$auth     = new Reply();
			$userID   = $this->session->Get('AuthUserID', null);
			$authUser = null;
			if (!isset($userID)) {
				// Check that valid tokens are set
				$validToken = $this->getValidToken($request);
				if ($validToken === false) {
					// kill any remaining cookies or sessions in this case
					$this->cookies->Delete('AuthToken');
					$this->session->Delete('AuthUserID');
					$this->session->Delete('AuthToken');
				} else {
					// a valid token was found - use it to pull the correct user
					$authUser = $this->userMapper->GetAuthorizedUserById($validToken->UserID);
					if ($authUser instanceof UserInterface) {
						$tokenString   = $this->setTokens($authUser, $validToken);
						$auth->Auth    = true;
						$auth->Token   = $tokenString;
						$auth->Message = 'Found valid token.';
					} else {
						$auth->Message = 'Tried to log in using token but user not found. '.$validToken->UserID;
					}
				}
			} else {
				// the user ID was found in the session, use that to log in
				$authUser = $this->userMapper->GetAuthorizedUserById($userID);
				if ($authUser instanceof UserInterface) {
					$auth->Auth    = true;
					$auth->Token   = $this->session->Get('AuthToken');
					$auth->Message = 'Logged in using session';
				} else {
					$auth->Message = 'Tried to log in with session but user not found';
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
	public function Login($username, $password, $remember = false) {
		$reply = new Reply();
		$user  = $this->userMapper->GetAuthorizedUserForUsernameAndPassword($username, $password);
		if ($user instanceof UserInterface) {
			$tokenString  = $this->setTokens($user, null, $remember);
			$reply->Auth  = true;
			$reply->User  = $user;
			$reply->Token = $tokenString;
			$this->auth   = $reply;
		}
		return $reply;
	}

	/**
	 * Log the user out and return a blank Reply
	 * @param \Fluxoft\Rebar\Http\Request $request
	 * @return Reply
	 */
	public function Logout(Request $request) {
		$auth = $this->GetAuthenticatedUser( $request);
		if ($auth->User instanceof UserInterface) {
			$token = $this->getValidToken($request);
			if ($token === false) {
				$token = null;
			}
			if (isset($token)) {
				$userID   = $token->UserID;
				$seriesID = $token->SeriesID;
			} else {
				$userID   = $auth->User->GetID();
				$seriesID = null;
			}
			$this->tokenMapper->DeleteAuthToken($userID, $seriesID);
			$this->cookies->Delete('AuthToken');
			$this->session->Delete('AuthUserID');
			$this->session->Delete('AuthToken');
		}
		$this->auth = new Reply();
	}



	private function getValidToken(Request $request) {
		$tokenString = $this->cookies->Get('AuthToken');
		if (!isset($tokenString)) {
			// See if the token is present in the URL
			$tokenString = $request->Get('AuthToken');
			if (!isset($tokenString)) {
				return false;
			}
		}

		list($authToken, $checksum) = explode('|', base64_decode($tokenString));

		if (hash('md5', $authToken) === $checksum) {
			$checkToken = new Token(null, null, null, $authToken);
			if (!$this->tokenMapper->CheckAuthToken($checkToken)) {
				return false;
			} else {
				return $checkToken;
			}
		} else {
			return false;
		}
	}
	private function setTokens(UserInterface $user, Token $token = null, $remember = false) {
		if (!isset($token)) {
			$token = new Token($user->GetID());
		}
		$checksum = hash('md5', (string) $token);
		$expires  = ($remember) ? strtotime('+'.$this->expiresDays.' day') : 0;

		$tokenString = base64_encode((string) $token . '|' . $checksum);

		$this->session->Set('AuthUserID', $token->UserID);
		$this->session->Set('AuthToken', $tokenString);
		$this->cookies->Set('AuthToken', $tokenString, $expires);

		$this->tokenMapper->SaveAuthToken($token);

		return $tokenString;
	}
}
