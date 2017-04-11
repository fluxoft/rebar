<?php
namespace Fluxoft\Rebar;

abstract class Model implements \Iterator, \ArrayAccess {
	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected $properties = [];
	/**
	 * Properties that have been changed from their original values but have not yet been written to the database.
	 * @var array $modProperties
	 */
	protected $modProperties = [];

	public function __construct(array $properties = []) {
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function GetProperties() {
		return $this->properties;
	}

	/**
	 * @return array
	 */
	public function GetModifiedProperties() {
		return $this->modProperties;
	}

	private $validationErrors = [];
	public function IsValid() {
		// Reset the error array
		$this->validationErrors = [];

		$valid = true;
		foreach ($this->properties as $key => $value) {
			$validationMethod = 'validate'.$key;
			if (is_callable([$this, $validationMethod])) {
				$validation = $this->$validationMethod($value);
				if ($validation !== true) {
					//
					$this->validationErrors[$key] = $validation;

					$valid = false;
				}
			}
		}
		return $valid;
	}
	public function GetValidationErrors() {
		return $this->validationErrors;
	}

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
		if (method_exists($this, $fnName)) {
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
	 * Override the magic __get() method to get the value for
	 * the specified member of the $properties array.  If a value
	 * exists in $modProperties, return that one, as that contains
	 * the updated value.  If the requested property does not
	 * exist in either array, try to find a method called
	 * "get[PropertyName].  If found, return the result of that
	 * function.
	 *
	 * @param $key
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __get($key) {
		$fnName = "get$key";
		if (method_exists($this, $fnName)) {
			return $this->$fnName();
		} elseif (array_key_exists($key, $this->properties)) {
			return $this->properties[$key];
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}

	public function __isset($key) {
		$fnName = "get$key";
		if (method_exists($this, $fnName)) {
			return ($this->$fnName() !== null);
		} elseif (array_key_exists($key, $this->modProperties) ||
			array_key_exists($key, $this->properties)) {
			return true;
		} else {
			throw new \InvalidArgumentException(sprintf('Property %s does not exist', $key));
		}
	}

	public function __unset($key) {
		$fnName = "set$key";
		if (method_exists($this, $fnName)) {
			$this->$fnName(null);
		} elseif (array_key_exists($key, $this->modProperties)) {
			unset($this->modProperties[$key]);
		} elseif (array_key_exists($key, $this->properties)) {
			$this->properties[$key] = null;
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot unset property %s', $key));
		}
	}

	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}

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

	// ArrayAccess implementation.
	public function offsetExists($offset) {
		return isset($this->$offset);
	}
	public function offsetGet($offset) {
		return $this->$offset;
	}
	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}
	public function offsetUnset($offset) {
		unset($this->$offset);
	}

	public function asArray() {
		$return = [];
		foreach ($this->properties as $key => $value) {
			$return[$key] = $this->$key;
		}
		return $return;
	}
}
