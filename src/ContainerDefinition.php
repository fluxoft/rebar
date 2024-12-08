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
	}
}
