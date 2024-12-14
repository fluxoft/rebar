<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\Data\SortInterface;

final class Sort implements SortInterface, \ArrayAccess, \Iterator {
	use GettableProperties;
	use ArrayAccessibleProperties;
	use IterableProperties;

	public function __construct(string $property, string $direction) {
		$this->properties['Property'] = $property;
		$this->setDirection($direction);
	}

	public static function Create(string $property, string $direction): SortInterface {
		return new self($property, $direction);
	}
	public function GetProperty(): string {
		return $this->Property;
	}
	public function GetDirection(): string {
		return $this->Direction;
	}

	private function setDirection(string $direction): void {
		$direction = strtoupper($direction);
		if (!in_array($direction, ['ASC', 'DESC'], true)) {
			throw new \InvalidArgumentException('Direction must be either "ASC" or "DESC".');
		}
		$this->properties['Direction'] = $direction;
	}
}
