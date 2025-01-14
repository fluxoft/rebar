<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;

interface PresenterInterface {
	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be formatted.
	 *
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array;
}
