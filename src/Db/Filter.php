<?php

namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\IterableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;

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

	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected $properties    = [
		'Property' => '',
		'Operator' => '=',
		'Value' => ''
	];
	protected $modProperties = [];

	public function __construct(
		string $property,
		string $operator,
		mixed  $value
	) {
		$this->properties['Property'] = $property;
		$this->properties['Operator'] = $operator;
		$this->properties['Value']    = $value;
	}
}
