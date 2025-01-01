<?php
namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;
use Fluxoft\Rebar\Http\Middleware\MiddlewareInterface;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class Cors implements MiddlewareInterface {
	protected bool $crossOriginEnabled         = false;
	protected array $crossOriginDomainsAllowed = [];
	protected array $allowedMethods            = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	public function __construct(
		array $allowedDomains = [],
		bool $enabled         = false,
		array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
	) {
		$this->crossOriginDomainsAllowed = $allowedDomains;
		$this->crossOriginEnabled        = $enabled;
		$this->allowedMethods            = $allowedMethods;
	}

	public function Process(Request $request, Response $response, callable $next): Response {
		$allowedMethods = array_map('strtoupper', $this->allowedMethods);
		$requestMethod  = $request->Method;

		// always allow OPTIONS requests
		if (!in_array('OPTIONS', $allowedMethods)) {
			$allowedMethods[] = 'OPTIONS';
		}

		// set CORS headers if configured
		if ($this->crossOriginEnabled) {
			$origin = $request->Headers('Origin');
			if (isset($origin)) {
				$allowedHeaders = $request->Headers('Access-Control-Request-Headers', '');
				$origin         = $request->Headers('Origin');
				if (in_array($origin, $this->crossOriginDomainsAllowed)) {
					$response->AddHeader('Access-Control-Allow-Origin', $origin);
					$response->AddHeader('Access-Control-Allow-Credentials', 'true');
					$response->AddHeader('Access-Control-Allow-Methods', implode(',', $allowedMethods));
					$response->AddHeader('Access-Control-Allow-Headers', $allowedHeaders);
				} else {
					throw new CrossOriginException(sprintf('The origin "%s" is not permitted.', $origin));
				}
			}
		}

		// Handle OPTIONS requests
		if (strtoupper($requestMethod) === 'OPTIONS') {
			$response->Halt(200, 'OK');
			return $response; // Return response immediately for OPTIONS requests
		}

		if (!in_array($requestMethod, $allowedMethods)) {
			throw new MethodNotAllowedException(sprintf(
				'The %s method is not permitted here.',
				$requestMethod
			));
		}

		return $next($request, $response);
	}
}
