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
		if (!empty($properties)) {
			if (count(array_intersect_key($this->properties, $properties)) === count($this->properties)) {
				foreach ($this->properties as $propertyName => $propertyValue) {
					$this->properties[$propertyName] = $properties[$propertyName];
				}
			} else {
				throw new \InvalidArgumentException('Property list does not match configured model property list.');
			}
		}
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
		} elseif (array_key_exists($key, $this->properties)) {
			if ($this->properties[$key] !== $value) {
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
		} elseif (array_key_exists($key, $this->modProperties)) {
			return $this->modProperties[$key];
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
}
