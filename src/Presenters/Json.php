<?php
namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

class Json implements PresenterInterface {
	protected $callback;
	public function __construct($callback = null) {
		$this->callback = $callback;
	}
	public function Render(Response $response, array $data) {
		$jsonString = $this->jsonEncode($data);
		if ($this->callback) {
			$response->AddHeader('Content-type', 'text/javascript');
			$response->Body = $this->callback.'('.$jsonString.');';
		} else {
			$response->AddHeader('Content-type', 'application/json');
			$response->Body = $jsonString;
		}
		$response->Send();
	}
	
	private function jsonEncode($data) {
		if (!is_array($data) && !is_object($data)) {
			return json_encode($data);
		} elseif (is_object($data)) {
			$bits = [];
			foreach($data as $key => $value) {
				$bits[] = "\"$key\":".$this->jsonEncode($value);
			}
			return "{".implode(",", $bits)."}";
		} else {
			$bits = [];
			if ($this->isIndexed($data)) {
				foreach ($data as $key => $value) {
					$bits[] = $this->jsonEncode($value);
				}
				return "[".implode(",", $bits)."]";
			} else {
				foreach($data as $key => $value) {
					$bits[] = "\"$key\":".$this->jsonEncode($value);
				}
				return "{".implode(",", $bits)."}";
			}
		}
	}
	
	// this bit of foolishness is needed because PHP doesn't
	// differentiate between a hash and an array:
	private function isIndexed($array) {
		return (bool) (array_values($array) === $array);
	}
}
