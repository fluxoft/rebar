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
abstract class Model implements \Iterator, \ArrayAccess {
	use GettableProperties;
	use SettableProperties;
	use IterableProperties;
	use ArrayAccessibleProperties;
	use StringableProperties;

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
			foreach ($properties as $name => $value) {
				$this->properties[$name] = $value;
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
	 * Checks any validate[PropertyName] functions, which should be protected and return
	 * either true or a string representing the validation error, which is set to the
	 * $validationErrors array.
	 * @return bool
	 */
	public function IsValid() {
		// Reset the error array
		$this->validationErrors = [];

		$valid = true;
		foreach ($this->properties as $key => $value) {
			$validationMethod = 'validate'.$key;
			if (is_callable([$this, $validationMethod])) {
				$validation = $this->$validationMethod($value);
				if ($validation !== true) {
					//
					$this->validationErrors[$key] = $validation;

					$valid = false;
				}
			}
		}
		return $valid;
	}
	private $validationErrors = [];
	/**
	 * Returns any validation errors that were found on the last run of IsValid()
	 * @return array
	 */
	public function GetValidationErrors() {
		return $this->validationErrors;
	}
}
