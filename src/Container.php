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
}
