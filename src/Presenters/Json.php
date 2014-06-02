<?php
namespace Fluxoft\Rebar\Presenters;

class Json implements PresenterInterface {
	protected $callback;
	public function __construct($callback = '') {
		$this->callback = $callback;
	}
	public function Render(array $data) {
		$jsonString = $this->jsonEncode($data);
		if (isset($_GET['callback'])) {
			header('Content-type: text/javascript');
			echo $_GET['callback'].'('.$jsonString.');';
		} else {
			header('Content-type: application/json');
			echo $jsonString;
		}
	}
	
	private function jsonEncode($data) {
		if (!is_array($data) && !is_object($data)) {
			return json_encode($data);
		} else if (is_object($data)) {
			$bits = array();
			foreach($data as $key => $value) {
				$bits[] = "\"$key\":".$this->jsonEncode($value);
			}
			return "{".implode(",",$bits)."}";
		} else {
			$bits = array();
			if ($this->isIndexed($data)) {
				foreach ($data as $key => $value) {
					$bits[] = $this->jsonEncode($value);
				}
				return "[".implode(",",$bits)."]";
			} else {
				foreach($data as $key => $value) {
					$bits[] = "\"$key\":".$this->jsonEncode($value);
				}
				return "{".implode(",",$bits)."}";
			}
		}
	}
	
	// this retarded little bit here is needed because PHP doesn't
	// differentiate between a hash and an array:
	private function isIndexed($array) {
		return (bool)(array_values($array) === $array);
	}
}