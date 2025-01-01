<?php

namespace Fluxoft\Rebar\Data;

interface FilterInterface {
	// Factory method to create a filter instance
	public static function Create(string $property, string $operator, mixed $value): self;

	// Accessors for filter details
	public function GetProperty(): string;
	public function GetOperator(): string;
	public function GetValue(): mixed;
}
