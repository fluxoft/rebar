<?php

namespace Fluxoft\Rebar\_Traits;

/**
 * Trait Properties
 * Provides a base definition for $properties and $modProperties arrays, used by various traits.
 * @package Fluxoft\Rebar\_Traits
 * @property array $properties Holds the internal array of property names and values.
 * @property array $modProperties Properties that have been changed from their original values.
 */
trait Properties {
	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected array $properties = [];
	/**
	 * Holds the internal array of property names and values that have been modified.
	 * @var array $modProperties
	 */
	protected array $modProperties = [];
}
