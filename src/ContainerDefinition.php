<?php

namespace Fluxoft\Rebar;

class ContainerDefinition {
	public function __construct(
		public string $Class,
		public array $Dependencies = []
	) {
		if (!class_exists($this->Class)) {
			throw new \InvalidArgumentException("Class '{$this->Class}' does not exist.");
		}

		// Validate that Dependencies is an array of strings
		foreach ($this->Dependencies as $dependency) {
			if (!is_string($dependency)) {
				throw new \InvalidArgumentException(
					"Dependency for class '{$this->Class}' must be a string key. Found: " . gettype($dependency)
				);
			}
		}
	}
}
