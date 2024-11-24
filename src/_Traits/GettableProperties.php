<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait GettableProperties
 * For classes that need to have properties that can be read
 * (use SettableProperties or UnsettableProperties for write/delete).
 * 
 * @package Fluxoft\Rebar\_Traits
 */
trait GettableProperties {
	use Properties;

	/**
	 * Override the magic __get() method to get the value for
	 * the specified member of the $properties array.  Use a "get[PropertyName]"
	 * function if available, or pull it directly from the array.
	 *
	 * @param $key
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __get($key) {
		$fnName = "get$key";
		if (is_callable([$this, $fnName])) {
			return $this->$fnName();
		} elseif (array_key_exists($key, $this->properties)) {
			return $this->properties[$key];
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}

	/**
	 * Override the magic __isset() method to get the value for
	 * the specified member of the $properties array. Use a "get[PropertyName]"
	 * function if available, or pull it directly from the array.
	 *
	 * Return true if the returned value is not null.
	 *
	 * @param $key
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function __isset($key) {
		$fnName = "get$key";
		if (is_callable([$this, $fnName])) {
			return ($this->$fnName() !== null);
		} elseif (array_key_exists($key, $this->properties)) {
			return ($this->properties[$key] !== null);
		} else {
			throw new \InvalidArgumentException(sprintf('Property %s does not exist', $key));
		}
	}
}
