<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException;
use Fluxoft\Rebar\Data\FilterInterface;

/**
 * @property string $Property
 * @property string $Operator
 * @property mixed  $Value
 */
final class Filter implements FilterInterface, \ArrayAccess, \Iterator {
	use GettableProperties;
	use ArrayAccessibleProperties;
	use IterableProperties;

	public function __construct(
		string $property,
		string $operator,
		mixed  $value
	) {
		$this->properties['Property'] = $property;
		$this->setOperator($operator);
		$this->setValue($value); // relies on Operator to set the value, so set it first
	}

	public static function Create(string $property, string $operator, mixed $value): FilterInterface {
		return new self($property, $operator, $value);
	}
	public function GetProperty(): string {
		return $this->properties['Property'];
	}
	public function GetOperator(): string {
		return $this->properties['Operator'];
	}
	public function GetValue(): mixed {
		return $this->properties['Value'];
	}

	/**
	 * @param string $operator
	 * @return void
	 * @throws InvalidFilterException
	 */
	protected function setOperator(string $operator): void {
		$this->properties['Operator'] = match (strtoupper($operator)) {
			'=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'BETWEEN', 'IS', 'IS NOT' => strtoupper($operator),
			default => throw new InvalidFilterException(
				'Invalid operator given. Must be one of the following: =, <, >, <=, >=, <>, !=, IN, LIKE, BETWEEN, IS, IS NOT'
			),
		};
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws InvalidFilterException
	 */
	protected function setValue(mixed $value): void {
		$thisOperator = $this->properties['Operator'];
		if (in_array($thisOperator, ['IS', 'IS NOT'], true)) {
			// For IS and IS NOT, value must be NULL
			if (!is_null($value)) {
				throw new InvalidFilterException(
					sprintf("Filter operator '%s' requires the value to be NULL.", $this->Operator)
				);
			}
			$this->properties['Value'] = $value;
			return;
		}
		if ($thisOperator === 'IN') {
			// for IN operator, value must be an array
			if (is_array($value)) {
				$this->properties['Value'] = $value;
			} else {
				throw new InvalidFilterException('Filter operator set to IN. Value must be an array of values.');
			}
			return;
		}
		if ($thisOperator === 'NOT IN') {
			// for NOT IN operator, value must be an array
			if (is_array($value)) {
				$this->properties['Value'] = $value;
			} else {
				throw new InvalidFilterException('Filter operator set to NOT IN. Value must be an array of values.');
			}
			return;
		}
		if ($thisOperator === 'BETWEEN') {
			// for BETWEEN, value must be an array with 2 values (low/high)
			if (is_array($value) && count($value) === 2) {
				$this->properties['Value'] = $value;
			} else {
				throw new InvalidFilterException(
					'Filter operator set to BETWEEN. Value must be an array with two elements, e.g. [$low, $high].'
				);
			}
			return;
		}
		// for other operators, value cannot be an array
		if (is_array($value)) {
			throw new InvalidFilterException('Cannot set Value to an array for the given Operator');
		}
		$this->properties['Value'] = $value;
	}
}
