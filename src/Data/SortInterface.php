<?php

namespace Fluxoft\Rebar\Data;

interface SortInterface {
	public static function Create(string $property, string $direction): self;
	public function GetProperty(): string;
	public function GetDirection(): string;
}
