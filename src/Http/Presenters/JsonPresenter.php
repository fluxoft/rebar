<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;

class JsonPresenter implements PresenterInterface {
	public function __construct(
		protected ?string $callback = null
	) {}

	public function SetCallback(string $callback): void {
		$this->callback = $callback;
	}

	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be formatted.
	 *
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array {
		try {
			if (empty($data)) {
				$jsonString = '{}';
			} else {
				$jsonString = json_encode($data, JSON_THROW_ON_ERROR);
			}
		} catch (\JsonException $e) {
			return [
				'body'    => json_encode(['error' => 'JSON encoding failed'], JSON_THROW_ON_ERROR),
				'status'  => 500,
				'headers' => ['Content-type' => 'application/json;charset=utf-8']
			];
		}

		if (!empty($this->callback)) {
			return [
				'body'    => $this->callback . '(' . $jsonString . ');',
				'status'  => 200,
				'headers' => ['Content-type' => 'text/javascript;charset=utf-8']
			];
		} else {
			return [
				'body'    => $jsonString,
				'status'  => 200,
				'headers' => ['Content-type' => 'application/json;charset=utf-8']
			];
		}
	}
}
