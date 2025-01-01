<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class Container
 * @package Fluxoft\Rebar
 */
class Container implements \ArrayAccess, ContainerInterface {
	protected $values  = [];
	protected $objects = [];

	/**
	 * Load definitions from an array and register them in the container.
	 *
	 * @param array $definitions
	 */
	public function LoadDefinitions(array $definitions): void {
		// First pass: register definitions and scalars
		foreach ($definitions as $key => $value) {
			if ($value instanceof ContainerDefinition) {
				$this[$key] = function () use ($value) {
					return new $value->Class(...array_map(fn($dep) => $this[$dep], $value->Dependencies));
				};
			} elseif (is_scalar($value) || is_null($value)) {
				$this[$key] = $value; // Allow direct scalar or null values
			} elseif (!is_string($value)) {
				throw new \InvalidArgumentException(
					"Invalid definition for key '$key'. Expected a ContainerDefinition, alias string, or scalar."
				);
			}
			// Skip alias resolution for now
		}

		// Second pass: resolve aliases
		foreach ($definitions as $key => $value) {
			if (is_string($value) && isset($this[$value])) {
				$this[$key] = fn() => $this[$value]; // Alias resolution
			} elseif (is_string($value) && !$this->offsetExists($value)) {
				continue; // This must be a scalar value, do not change it.
			}
		}

		// Third pass: validate dependencies
		foreach ($definitions as $key => $value) {
			if ($value instanceof ContainerDefinition) {
				foreach ($value->Dependencies as $dependency) {
					if (!isset($this[$dependency])) {
						throw new \InvalidArgumentException(
							"Invalid dependency '$dependency' for key '$key'. It is not defined in the container."
						);
					}
				}
			}
		}
	}

	// PSR-11 implementation
	public function has($id): bool {
		return $this->offsetExists($id);
	}
	public function get($id): mixed {
		return $this->offsetGet($id);
	}

	// ArrayAccess
	public function offsetExists($offset): bool {
		return isset($this->values[$offset]);
	}
	public function offsetGet($offset): mixed {
		if (!$this->offsetExists($offset)) {
			throw new NotFoundException(sprintf('Value "%s" is not defined.', $offset));
		}
		if (is_callable($this->values[$offset])) {
			if ($this->objects[$offset]) {
				return $this->values[$offset];
			} else {
				$this->objects[$offset] = true;
				$this->values[$offset]  = $this->values[$offset]($this);
				return $this->values[$offset];
			}
		} else {
			return $this->values[$offset];
		}
	}
	public function offsetSet($offset, $value): void {
		$this->objects[$offset] = false;
		$this->values[$offset]  = $value;
	}
	public function offsetUnset($offset): void {
		unset($this->values[$offset]);
	}

	// Enable object-style property access
	public function __isset($key) {
		return $this->offsetExists($key);
	}
	public function __get($key) {
		return $this->offsetGet($key);
	}
	public function __set($key, $value) {
		$this->offsetSet($key, $value);
	}
	public function __unset($key) {
		$this->offsetUnset($key);
	}
}
