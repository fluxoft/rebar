<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use Fluxoft\Rebar\Data\Db\Exceptions\InvalidFilterException;

/**
 * @property string Property
 * @property string Operator
 * @property mixed  Value
 */
class Filter implements \ArrayAccess, \Iterator {
	use GettableProperties;
	use SettableProperties;
	use ArrayAccessibleProperties;
	use IterableProperties;

	public function __construct(
		string $property,
		string $operator,
		mixed  $value
	) {
		$this->properties = [
			'Property' => $property,
			'Operator' => $operator,
			'Value'    => $value
		];
		$this->Property   = $property;
		$this->Operator   = $operator;
		$this->Value      = $value;
	}

	/**
	 * @param string $operator
	 * @return void
	 * @throws InvalidFilterException
	 */
	protected function setOperator(string $operator):void {
		$this->properties['operator'] = match (strtoupper($operator)) {
			'=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'LIKE', 'BETWEEN' => strtoupper($operator),
			default => throw new InvalidFilterException(
				'Invalid operator given. Must be one of the following: =, <, >, <=, >=, <>, !=, IN, LIKE, BETWEEN'
			),
		};
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws InvalidFilterException
	 */
	protected function setValue(mixed $value): void {
		if ($this->Operator === 'IN') {
			// for IN operator, value must be an array
			if (is_array($value)) {
				$this->properties['Value'] = $value;
			} else {
				throw new InvalidFilterException('Filter operator set to IN. Value must be an array of values.');
			}
			return;
		}
		if ($this->Operator === 'BETWEEN') {
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
