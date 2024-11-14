<?php

namespace Fluxoft\Rebar\Http\Middleware;

interface MiddlewareInterface {
	public function Process(Request $request, Response $response, callable $next): Response;
}