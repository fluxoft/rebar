<?php

namespace Fluxoft\Rebar\_Traits;

trait StringableProperties {
	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}
}
