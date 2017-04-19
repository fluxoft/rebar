<?php

namespace Fluxoft\Rebar\_Traits;

trait IterableProperties {
	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected $properties = [];

	// Iterator interface implementation.
	private $position = 0;
	public function rewind() {
		$this->position = 0;
	}
	public function current() {
		$keys         = array_keys($this->properties);
		$propertyName = $keys[$this->position];
		return $this->$propertyName;
	}
	public function key() {
		$keys = array_keys($this->properties);
		return $keys[$this->position];
	}
	public function next() {
		++$this->position;
	}
	public function valid() {
		return !($this->position > count($this->properties)-1);
	}
}
