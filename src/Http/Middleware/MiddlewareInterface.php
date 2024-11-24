<?php

namespace Fluxoft\Rebar\Http\Middleware;

use Fluxoft\Rebar\Http\Request; // Import the correct Request class
use Fluxoft\Rebar\Http\Response; // Import the correct Response class

interface MiddlewareInterface {
 public function Process(Request $request, Response $response, callable $next): Response;
}
