<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait ArrayAccessibleProperties
 * Should only be used with classes that are also using GettableProperties
 * and either SettableProperties or UnsettableProperties
 *
 * @package Fluxoft\Rebar\_Traits
 */
trait ArrayAccessibleProperties {
	use Properties;

	// ArrayAccess implementation.
	public function offsetExists($offset): bool {
		return isset($this->$offset);
	}
	public function offsetGet($offset): mixed {
		return $this->$offset;
	}
	public function offsetSet($offset, $value): void {
		$this->$offset = $value;
	}
	public function offsetUnset($offset): void {
		unset($this->$offset);
	}
	public function toArray(): array {
		$return = [];
		foreach ($this->properties as $key => $value) {
			$return[$key] = $this->$key;
		}
		return $return;
	}
}
