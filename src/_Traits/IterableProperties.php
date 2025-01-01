<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait IterableProperties
 * For classes that need to have properties that can be iterated over.
 * 
 * @package Fluxoft\Rebar\_Traits
 */
trait IterableProperties {
	use Properties;

	// Iterator interface implementation.
	private int $position = 0;
	public function rewind(): void {
		$this->position = 0;
	}
	public function current(): mixed {
		$keys         = array_keys($this->properties);
		$propertyName = $keys[$this->position];
		return $this->$propertyName;
	}
	public function key(): mixed {
		$keys = array_keys($this->properties);
		return $keys[$this->position];
	}
	public function next(): void {
		++$this->position;
	}
	public function valid(): bool {
		return !($this->position > count($this->properties)-1);
	}
}
