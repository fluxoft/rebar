<?php
namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

interface PresenterInterface {
	public function Render(Response $response, array $data);
}