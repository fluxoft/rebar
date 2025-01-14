<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Http\Response;

class DebugPresenter implements PresenterInterface {
	/** @inheritDoc */
	public function Format(array $data): array {
		$body  = "*** The page's data set: ***\n\n";
		$body .= $this->renderData($data);
		$body .= "\n****************************\n";

		return [
			'body'    => $body,
			'status'  => 200,
			'headers' => ['Content-type' => 'text/plain']
		];
	}

	private $tab = "	";
	protected function renderData($data, $indent = null) {
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
