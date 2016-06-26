<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Db\Token;
use Fluxoft\rebar\Auth\Db\TokenMapper;
use Fluxoft\Rebar\Auth\Db\User;
use Fluxoft\Rebar\Auth\Db\UserMapper;
use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Session;

/**
 * Class Web
 * @package Fluxoft\Rebar\Auth
 */
class Web implements AuthInterface {
	/** @var UserMapper */
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
			$auth   = new Reply();
			$userID = $this->session->Get('AuthUserID', null);
			if (!isset($userID)) {
				// Check that valid tokens are set
				$validToken = $this->getValidToken($request);
				if ($validToken === false) {
					// kill any remaining cookies or sessions in this case
					$this->cookies->Delete('AuthToken');
					$this->session->Delete('AuthUserID');
					$this->session->Delete('AuthToken');
				} else {
					$authUser    = $this->loginWithToken($validToken);
					$auth->Auth  = true;
					$auth->User  = $authUser;
					$auth->Token = $validToken;
				}
			} else {
				$authUser    = $this->userMapper->GetOneById($userID);
				$auth->Auth  = true;
				$auth->User  = $authUser;
				$auth->Token = $this->session->Get('AuthToken');
			}
			$this->auth = $auth;
		}
		return $this->auth;
	}

	public function Login($username, $password, $remember = false) {
		$reply = new Reply();
		$user  = $this->userMapper->GetOneForUsernameAndPassword($username, $password);
		if ($user instanceof User) {
			$tokenString  = $this->setTokens($user, null, $remember);
			$reply->Auth  = true;
			$reply->User  = $user;
			$reply->Token = $tokenString;
			$this->auth   = $reply;
		}
		return $reply;
	}

	public function Logout(Request $request) {
		$auth = $this->GetAuthenticatedUser( $request);
		if ($auth->User instanceof User) {
			$token = $this->getValidToken($request);
			if ($token === false) {
				$token = null;
			}
			$this->unsetTokens($auth->User, $token);
		}
		$this->auth = new Reply();
	}



	private function loginWithToken (Token $token) {
		$user = $this->userMapper->GetOneById($token->UserID);
		if ($user instanceof User) {
			$this->setTokens($user, $token);
		}
		return $user;
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
	private function setTokens(User $user, Token $token = null, $remember = false) {
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
	private function unsetTokens(User $user, Token $token = null) {
		if (isset($token)) {
			$userID   = $token->UserID;
			$seriesID = $token->SeriesID;
		} else {
			$userID   = $user->GetID();
			$seriesID = null;
		}

		$this->tokenMapper->DeleteAuthToken($userID, $seriesID);
		$this->cookies->Delete('AuthToken');
		$this->session->Delete('AuthUserID');
		$this->session->Delete('AuthToken');
	}
}
