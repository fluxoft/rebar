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

	public function Render(Response $response, array $data): void {
		try {
			if (empty($data)) {
				$jsonString = '{}';
			} else {
				$jsonString = json_encode($data, JSON_THROW_ON_ERROR);
			}
		} catch (\JsonException $e) {
			$response->AddHeader('Content-type', 'application/json;charset=utf-8');
			$response->Status = 500;
			$response->Body   = json_encode(['error' => 'JSON encoding failed'], JSON_THROW_ON_ERROR);
			$response->Send();
			return;
		}

		if (!empty($this->callback)) {
			$response->AddHeader('Content-type', 'text/javascript;charset=utf-8');
			$response->Body = $this->callback . '(' . $jsonString . ');';
		} else {
			$response->AddHeader('Content-type', 'application/json;charset=utf-8');
			$response->Body = $jsonString;
		}
		$response->Send();
	}
}
