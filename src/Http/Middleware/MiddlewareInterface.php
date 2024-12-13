<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

interface MiddlewareInterface {
	public function Process(Request $request, Response $response, callable $next): Response;
}
