<?php

namespace Fluxoft\Rebar\_Traits;

trait StringableProperties {
	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected $properties = [];

	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}
}
