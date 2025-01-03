<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use Fluxoft\Rebar\_Traits\StringableProperties;

/**
 * Class Model
 * @package Fluxoft\Rebar
 */
abstract class Model implements \Iterator, \ArrayAccess, \JsonSerializable {
	use GettableProperties;
	use SettableProperties;
	use IterableProperties;
	use ArrayAccessibleProperties;
	use StringableProperties;

	protected static array $defaultProperties = []; // Define defaults in subclasses

	public function __construct(array $properties = []) {
		foreach (static::$defaultProperties as $key => $defaultValue) {
			$this->properties[$key] = $properties[$key] ?? $defaultValue;
		}
	}

	/**
	 * @return array
	 */
	public function GetProperties(): array {
		return $this->properties;
	}

	/**
	 * @return array
	 */
	public function GetModifiedProperties(): array {
		return $this->modProperties;
	}

	/**
	 * Checks any validate[PropertyName] functions, which should be protected and return
	 * either true or a string representing the validation error, which is set to the
	 * $validationErrors array.
	 * @return bool
	 */
	public function IsValid(): bool {
		// Reset the error array
		$this->validationErrors = [];

		$valid = true;
		foreach ($this->properties as $key => $value) {
			$validationMethod = 'validate'.$key;
			if (is_callable([$this, $validationMethod])) {
				$validation = $this->$validationMethod($value);
				if ($validation !== true) {
					$this->validationErrors[$key] = $validation;
					$valid                        = false;
				}
			}
		}
		return $valid;
	}
	private array $validationErrors = [];

	/**
	 * Returns any validation errors that were found on the last run of IsValid()
	 * @return array
	 */
	public function GetValidationErrors(): array {
		return $this->validationErrors;
	}

	/**
	 * This is for initializing a model's properties array without setting the values in the modProperties array,
	 * as would happen if setting each property through the object's setter methods.
	 * @param array $initialProperties
	 */
	public function InitializeProperties(array $initialProperties): void {
		foreach ($initialProperties as $key => $value) {
			if (array_key_exists($key, $this->properties)) {
				$this->properties[$key] = $value;
			}
		}
	}

	// Implementing JsonSerializable
	public function jsonSerialize(): array {
		return $this->properties;
	}
}
