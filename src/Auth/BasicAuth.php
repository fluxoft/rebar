<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\BasicAuthChallengeException;
use Fluxoft\Rebar\Http\Request;
use RuntimeException;

/**
 * Class Basic
 * @package Fluxoft\Rebar\Auth
 */
class BasicAuth implements AuthInterface {
	public function __construct(
		protected UserMapperInterface $userMapper,
		protected string $realm = 'Restricted Area',
		protected string $message = 'Unauthorized'
	) {}

	/**
	 * {@inheritdoc}
	 */
	public function GetAuthenticatedUser(Request $request): ?Reply {
		$basicAuthUser = $request->Server('PHP_AUTH_USER');

		if (!isset($basicAuthUser)) {
			throw new BasicAuthChallengeException($this->realm, $this->message);
		}
		return $this->Login($request, $basicAuthUser, $request->Server('PHP_AUTH_PW', ''));
	}

	/**
	 * {@inheritdoc}
	 * Note: The $remember parameter is not used in this implementation.
	 */
	public function Login(Request $request, string $username, string $password, bool $remember = false): Reply {
		// unused in this implementation
		unset($request, $remember);
		
		$reply       = new Reply();
		$user        = $this->userMapper->GetAuthorizedUserForUsernameAndPassword($username, $password);
		$reply->Auth = true;
		$reply->User = $user;
		return $reply;
	}

	/**
	 * {@inheritdoc}
	 */
	public function Logout(Request $request): Reply {
		//unused
		unset($request);

		throw new RuntimeException('Logout is not supported with Basic Auth');
	}
}
