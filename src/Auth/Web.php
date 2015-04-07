<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Session;

class Web implements AuthInterface {
	/** @var UserMapper */
	protected $userMapper;
	/** @var Cookies */
	protected $cookies;
	/** @var Session */
	protected $session;
	/** @var array */
	protected $config;

	/** @var bool|User */
	protected $authenticatedUser = false;

	public function __construct(
		UserMapper $userMapper,
		Cookies $cookies,
		Session $session,
		array $config = []
	) {
		$this->userMapper = $userMapper;
		$this->cookies    = $cookies;
		$this->session    = $session;

		$defaultConfig = [
			'rememberDays' => 30
		];
		$this->config  = array_merge($defaultConfig, $config);
	}

	public function Login($username, $password, $remember = false) {
		$this->Logout();

		$user = $this->userMapper->GetOneForUsernameAndPassword($username, $password);
		if ($user instanceof User) {
			$this->setTokens($user, null, $remember);
		}
		return $user;
	}

	public function LoginWithToken (Token $token) {
		$this->Logout();

		$user = $this->userMapper->GetOneById($token->UserID);
		if ($user instanceof User) {
			$this->setTokens($user, $token);
		}
		return $user;
	}

	public function Logout() {
		$user = $this->authenticatedUser;
		if ($user instanceof User) {
			$token = $this->getValidToken();
			if ($token === false) {
				$token = null;
			}
			$this->unsetTokens($user, $token);
		}
	}

	/**
	 * @param Token $token
	 * @return bool|User
	 */
	public function GetAuthenticatedUser(Token $token = null) {
		if ($this->authenticatedUser === false) {
			$userID = $this->session->Get('AuthUserID', false);
			if ($userID === false) {
				// Check that valid tokens are set
				$validToken = $this->getValidToken();
				if ($validToken === false) {
					// kill any remaining cookies or sessions in this case
					$this->cookies->Delete('AuthToken');
					$this->cookies->Delete('AuthTokenChecksum');
					$this->session->Delete('AuthUserID');
					$this->authenticatedUser = false;

					if (isset($token)) {
						$this->authenticatedUser = $this->LoginWithToken($token);
					}
				} else {
					$this->authenticatedUser = $this->LoginWithToken($token);
				}
			} else {
				$this->authenticatedUser = $this->userMapper->GetOneById($userID);
			}
		}
		return $this->authenticatedUser;
	}

	private function getValidToken() {
		$authToken = $this->cookies->Get('AuthToken');
		$checksum  = $this->cookies->Get('AuthTokenChecksum');
		if (!isset($authToken)) {
			return false;
		}
		if (!isset($checksum)) {
			return false;
		}
		if (hash('md5', $authToken) === $checksum) {
			$checkToken = new Token(null, null, null, $authToken);
			if (!$this->userMapper->CheckAuthToken($checkToken)) {
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
		$expires  = ($remember) ? strtotime('+'.$this->config['rememberDays'].' day') : 0;

		$this->session->Set('AuthUserID', $token->UserID);
		$this->cookies->Set('AuthToken', (string) $token, $expires);
		$this->cookies->Set('AuthTokenChecksum', $checksum, $expires);

		$this->userMapper->SaveAuthToken($token);
	}

	private function unsetTokens(User $user, Token $token = null) {
		if (isset($token)) {
			$userID   = $token->UserID;
			$seriesID = $token->SeriesID;
		} else {
			$userID   = $user->GetID();
			$seriesID = null;
		}

		$this->userMapper->DeleteAuthToken($userID, $seriesID);
		$this->cookies->Delete('AuthToken');
		$this->cookies->Delete('AuthTokenChecksum');
		$this->session->Delete('AuthUserID');
	}
}
