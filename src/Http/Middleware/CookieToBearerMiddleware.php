<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

/**
 * Class CookieToBearerMiddleware
 * This middleware will check for an Authorization header and if it doesn't exist, it will check for an AccessToken
 * cookie and if it exists, it will add a Bearer token to the Authorization header. This is useful for APIs that
 * exist in systems where authentication may have happened in a traditional web application and the access token
 * is stored in a cookie.
 * @package Fluxoft\Rebar\Http\Middleware
 */
class CookieToBearerMiddleware implements MiddlewareInterface {
	public function Process(Request $request, Response $response, callable $next): Response {
		// Check for the Authorization header
		$authHeader = $request->Headers->Get('Authorization');
		if (!isset($authHeader) || stripos($authHeader, 'Bearer ') !== 0) {
			// Look for an AccessToken cookie
			$accessToken = $request->Cookies->Get('AccessToken');
			if (isset($accessToken)) {
				// Add the Bearer token to the Authorization header
				$request->Headers->Set('Authorization', 'Bearer ' . $accessToken);
			}
		}

		// Continue processing the request
		return $next($request, $response);
	}
}
