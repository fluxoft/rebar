<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 5/26/14
 * Time: 1:36 AM
 */

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Exceptions\AuthenticationException;

class Web implements AuthInterface {
	protected $userFactory;
	protected $config;

	public function __construct(
		UserFactory $userFactory,
		array $config = null
	) {
		if (session_id() == '') {
			session_start();
		}
		$this->userFactory = $userFactory;
		$this->setConfig($config);
	}

	public function GetAuthenticatedUser () {
		$autoLogin = $this->AutoLogin();
		if (isset($autoLogin['success'])) {

		} else {
			throw new AuthenticationException('Could not authenticate user.');
		}
	}

	public function IsLoggedIn() {
		return (isset($_SESSION['LoggedIn']) && $_SESSION['LoggedIn'] === true);
	}

	public function AutoLogin ($tokenString = '') {
		$return = array();

		// if the user is already logged in, this can all be skipped
		if ($this->IsLoggedIn()) {
			$return['success'] = 'User is already logged in.';
		} else {
			if ($this->config['RemoteAuth']['Enabled'] === true) {
				// todo: add remote validation
				$return['success'] = 'Remote validation.';
			} else {
				$return = $this->ValidateLoginToken($tokenString);
			}
		}

		return $return;
	}

	public function ValidateLoginToken($tokenString = '') {
		$checkTokenString = '';
		if (strlen($tokenString)) {
			$checkTokenString = $tokenString;
		} elseif (isset($_COOKIE['AuthToken'])) {
			$checkTokenString = $_COOKIE['AuthToken'];
		} elseif (isset($_COOKIE['RememberMe'])) {
			$checkTokenString = $_COOKIE['RememberMe'];
		}

		$return = array();

		if (strlen($checkTokenString) === 0) {
			$return['error'] = 'Found no token to validate.';
		} else {
			try {
				$checkToken = new Token(0, '', '', $checkTokenString);
			} catch (InvalidTokenException $e) {
				return array('error' => $e->getMessage());
			}

			/** @var $checkUser \Fluxoft\Rebar\Auth\UserModel */
			$checkUser = $this->userFactory->GetByToken($checkToken);

			// Regardless of whether there were matches for the entire triplet,
			// any entries for this userID/seriesID combo should be deleted,
			// because if someone submitted an incorrect token with a correct
			// userID and seriesID, it may be a fraudulent login attempt, so
			// it's best to just go ahead and blow away that seriesID altogether
			// and just force the user to go ahead and log in again from this
			// device.
			//$checkUser->DeleteAuthTokens($checkToken->SeriesID);

			if ($checkUser->IsTokenValid($checkToken)) {
				// If there was a match, now a new token should be set up and
				// added to the database and to the user's cookie (so that on
				// every new visit, they are assigned a new token with a new
				// expiration date).
				$newToken = $checkUser->SaveAuthToken($checkToken->SeriesID);
				$this->saveSession($checkUser);
				$this->saveAuthTokenCookie($newToken);
				$return['success'] = 'Successfully validated authentication token. New token issued.';
				$return['token'] = $newToken;
			} else {
				// If there were no matches in the database for the given set of
				// credentials presented in the cookie, this may be a fraudulent
				// login attempt.  For safety reasons, delete all login tokens
				// associated with this userID, in case more than one device
				// (seriesID) was compromised.  This will force the user to log
				// in again on every device from which they had previously
				// saved a login, but that's better than having their account
				// compromised because they lost their cookies.
				$this->killSession();
				$this->killAuthTokenCookie();
				$checkUser->DeleteAuthTokens();
				$return['error'] = 'Possible fraudulent login detected. All authentication tokens destroyed.';
			}
		}

		return $return;
	}

	public function Login($username, $password, $remember = false) {
		$return = array();
		$user = null;
		try {
			$user = $this->userFactory->GetByUsernameAndPassword($username, $password);
		} catch (InvalidPasswordException $e) {
			$return['error'] = $e->getMessage();
		} catch (UserNotFoundException $e) {
			$return['error'] = $e->getMessage();
		} catch (\Exception $e) {
			$return['error'] = $e->getMessage();
		}

		if (isset($user)) {
			$token = $user->SaveAuthToken();
			$this->saveSession($user);
			$this->saveAuthTokenCookie($token, $remember);
			$return = array('user' => $user, 'token' => $token);
		}
		return $return;
	}

	public function Logout() {
		//todo: add remote auth stuff
		// send a message to remote auth server to log out as well

		if (isset($_COOKIE['AuthToken'])) {
			$token = new Token(0, '', '', $_COOKIE['AuthToken']);
			/** @var \Fluxoft\Rebar\Auth\UserModel $user */
			$user = $this->userFactory->GetByToken($token);
			$user->DeleteAuthTokens($token->SeriesID);

			// now destroy the cookies by setting their expiration date to the past
			$this->killAuthTokenCookie();
		}

		// destroy the session
		$this->killSession();
	}

	private function saveSession(UserModel $user) {
		$_SESSION['LoggedIn'] = true;
		$_SESSION['UserID'] = $user->GetID();
		if (!empty($this->config['SessionExtras'])) {
			foreach ($this->config['SessionExtras'] as $extra) {
				if (isset($user[$extra])) {
					$_SESSION[$extra] = $user->$extra;
				}
			}
		}
	}

	private function killSession() {
		if (!isset($_SESSION)) {
			session_start();
		}
		$_SESSION = array();

		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		session_destroy();
	}

	private function saveAuthTokenCookie(Token $token, $persist = true) {
		$expire = strtotime('+' . $this->config['RememberMe']['Days'] . ' days');
		$domain = $this->config['CookieDomain'];
		setcookie('AuthToken', (string) $token, 0, '/', $domain);
		if ($persist && $this->config['RememberMe']['Enabled']) {
			setcookie('RememberMe', (string) $token, $expire, '/', $domain);
		}
	}

	private function killAuthTokenCookie() {
		$domain = $this->config['CookieDomain'];
		setcookie('AuthToken', null, 0, '/', $domain);
		setcookie('RememberMe', null, 0, '/', $domain);
	}

	private function setConfig (array $config = null) {
		$defaultConfig = array(
			'LoginRedirect' => '/auth/login',
			'CookieDomain' => '.'.getenv('HTTP_HOST'),
			'RememberMe' => array(
				'Enabled' => false,
				'Days' => 7
			),
			'RemoteAuth' => array(
				'Enabled' => false,
				'LoginUrl' => '',
				'LogoutUrl' => '',
				'ValidationUrl' => ''
			),
			'SessionExtras' => array()
		);
		if (is_array($config)) {
			if (isset($config['LoginRedirect'])) {
				$defaultConfig['LoginRedirect'] = $config['LoginRedirect'];
			}
			if (isset($config['CookieDomain'])) {
				$defaultConfig['CookieDomain'] = $config['CookieDomain'];
			}
			if (isset($config['RememberMe'])) {
				$defaultConfig['RememberMe'] = array_merge($defaultConfig['RememberMe'], $config['RememberMe']);
			}
			if (isset($config['RemoteAuth'])) {
				$defaultConfig['RemoteAuth'] = array_merge($defaultConfig['RemoteAuth'], $config['RemoteAuth']);
			}
			if (isset($config['SessionExtras'])) {
				$defaultConfig['SessionExtras'] = $config['SessionExtras'];
			}
		}
		$this->config = $defaultConfig;
	}
} 