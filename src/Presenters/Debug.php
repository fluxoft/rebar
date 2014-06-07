<?php
namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

class Debug implements PresenterInterface {
	public function Render(Response $response, array $data) {
		header('Content-type: text/plain');
		echo "*** The page's data set: ***\n\n";
		echo $this->renderData($data);
		echo "\n****************************\n";
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
			} else if (is_object($value)) {
				$return .= "$padding$key (Object) => (\n";
				foreach ($value as $prop => $val) {
					$return .= $padding.$padding.'['.$prop.'] => '.$val."\n";
				}
				$return .= "$padding)\n";
			} else {
				$return .= "$padding$key (Array) => [\n";
				$return .= $this->renderData($value,$padding);
				$return .= "$padding]\n";
			}
		}
		return $return;
	}
}