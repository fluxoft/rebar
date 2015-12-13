<?php
namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

class Debug implements PresenterInterface {
	public function Render(Response $response, array $data) {
		$response->AddHeader('Content-type', 'text/plain');
		$response->Body .= "*** The page's data set: ***\n\n";
		$response->Body .= $this->renderData($data);
		$response->Body .= "\n****************************\n";
		$response->Send();
	}
	
	private $tab = "    ";
	private function renderData($data,$indent = null) {
		if (!isset($indent)) {
			$padding = '';
		} else {
			$padding = $indent.$this->tab;
		}
		$return = '';
		foreach($data as $key => $value) {
			if (!is_array($value) && (!is_object($value))) {
				$return .= "$padding$key => $value\n";
			} elseif (is_object($value)) {
				$return .= "$padding$key (Object) => (\n";
				$return .= $this->renderData($value, $padding);
				$return .= "$padding)\n";
			} else {
				$return .= "$padding$key (Array) => [\n";
				$return .= $this->renderData($value, $padding);
				$return .= "$padding]\n";
			}
		}
		return $return;
	}
}
