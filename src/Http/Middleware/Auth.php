<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\BasicAuthChallengeException;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class Auth implements MiddlewareInterface {
	private array $authConfig = [];

	/**
	 * @param array $authConfig Associative array of paths and their corresponding AuthInterface implementations.
	 */
	public function __construct(array $authConfig) {
		// reverse key sort this array so that more specific paths are first
		$this->authConfig = krsort($authConfig);
	}

	public function SetAuthForPath(string $path = '/', AuthInterface $auth): void {
		$this->authConfig[$path] = $auth;
		// reverse key sort this array so that more specific paths are first
		krsort($this->authConfig);
	}

	public function Process(Request $request, Response $response, callable $next): Response {
		$path = $request->Path;
		$auth = $this->getAuthForPath($path);

		if ($auth instanceof AuthInterface) {
			$this->authenticateRequest($request, $response, $auth);
		}

		// Call the next middleware in the stack
		return $next($request, $response);
	}

	private function authenticateRequest(Request $request, Response $response, AuthInterface $auth): void {
		// Attach this AuthInterface to the request
		$request->Auth = $auth;

		try {
			// Check if the user is authenticated
			$authReply = $auth->GetAuthenticatedUser($request);
			if (!$authReply->Auth) {
				// Halt processing and return a 403 response
				$response->Halt(403, 'Access denied');
			}

			// Attach the authenticated user to the request
			$request->AuthenticatedUser = $authReply->User;
		} catch (BasicAuthChallengeException $e) {
			// The user is not authenticated, so send a 401 response
			$response->AddHeader('WWW-Authenticate', 'Basic realm="'.$e->getRealm().'"');
			$response->Halt(401, $e->getMessage());
		}
	}

	/**
	 * Determine the appropriate AuthInterface for a given path.
	 *
	 * @param string $path
	 * @return AuthInterface|null
	 */
	private function getAuthForPath(string $path): ?AuthInterface {
		foreach ($this->authConfig as $route => $authInterface) {
			if (strpos($path, $route) === 0) {
				return $authInterface;
			}
		}
		return null;
	}
}
