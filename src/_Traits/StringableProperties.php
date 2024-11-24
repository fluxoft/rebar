<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait StringableProperties
 * For classes that need to have properties that can be converted to a string.
 * 
 * @package Fluxoft\Rebar\_Traits
 */
trait StringableProperties {
	use Properties;

	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}
}
