<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait SettableProperties
 * For classes that need to have properties that can be set.
 *
 * @package Fluxoft\Rebar\_Traits
 */
trait SettableProperties {
	use Properties;

	/**
	 * Override the magic __set() method to set the values
	 * for members of the $properties array.
	 *
	 * @param $key
	 * @param $value
	 * @throws \InvalidArgumentException
	 */
	public function __set($key, $value) {
		$fnName = "set$key";
		if (is_callable([$this, $fnName])) {
			$this->$fnName($value);
			// Set the properties array with the given value so that the
			// changed value is available, but not the modProperties array,
			// as the custom setter function defined for this model should
			// take care of actual modifications.
			if (array_key_exists($key, $this->properties)) {
				$this->properties[$key] = $value;
			}
		} elseif (array_key_exists($key, $this->properties)) {
			// set both the properties and modProperties keys
			if ($this->properties[$key] !== $value) {
				$this->properties[$key]    = $value;
				$this->modProperties[$key] = $value;
			}
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}

	/**
	 * Override the magic __unset method to handle setting properties to null.
	 *
	 * @param $key
	 * @throws \InvalidArgumentException
	 */
	public function __unset($key) {
		$fnName = "set$key";
		if (is_callable([$this, $fnName])) {
			$this->$fnName(null);
		} elseif (array_key_exists($key, $this->modProperties)) {
			unset($this->modProperties[$key]);
			$this->properties[$key] = null;
		} elseif (array_key_exists($key, $this->properties)) {
			$this->properties[$key] = null;
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot unset property %s', $key));
		}
	}
}
