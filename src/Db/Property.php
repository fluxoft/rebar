<?php

namespace Fluxoft\Rebar\Db;

/**
 * Class Property
 * This class is a property mapper, used to map properties to database columns.
 * It can be used to implement property-specific logic as needed.
 * @package Fluxoft\Rebar\Db
 * @property string Column
 * @property string Type
 * @property-read bool IsWriteable
 * @property-read bool IsAggregate
 */
class Property {
	use \Fluxoft\Rebar\_Traits\GettableProperties;
	use \Fluxoft\Rebar\_Traits\SettableProperties;

	private $properties = [
		'Column' => '',
		'Type' => 'string'
	];
	
	protected ?bool $isAggregate = null;
	protected ?bool $isWriteable = null;

	private const VALID_TYPES = [
		'string',
		'integer',
		'float',
		'boolean',
		'datetime',
		'date',
		'time',
		'text', // Optional types, depending on use cases
		'binary'
	];    

	/**
	 * Constructor to initialize a Property.
	 *
	 * @param string $column Column name in the database.
	 * @param string $type Data type of the property (e.g., string, integer).
	 */
	public function __construct(string $column, string $type = 'string') {
		$this->Column = $column;
		$this->Type   = $type;
	}

	/**
	 * Determine if this property is writeable.
	 *
	 * @return bool
	 */
	protected function getIsWriteable(): bool {
		// Use cached result if available
		if (isset($this->isWriteable)) {
			return $this->isWriteable;
		}

		// Aggregate properties are not writeable
		if ($this->IsAggregate) {
			$this->isWriteable = false;
			return $this->isWriteable;
		}

		// Foreign table columns are not writeable (e.g., "table.column")
		if (strpos($this->Column, '.') !== false) {
			$this->isWriteable = false;
			return $this->isWriteable;
		}

		// Default to writeable
		$this->isWriteable = true;
		return $this->isWriteable;
	}

	/**
	 * Determine if this property is an aggregate based on the column definition.
	 *
	 * @return bool
	 */
	protected function getIsAggregate(): bool {
		// Use cached result if available
		if (isset($this->isAggregate)) {
			return $this->isAggregate;
		}
		$knownAggregates = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];

		foreach ($knownAggregates as $aggregate) {
			if (stripos($this->Column, $aggregate . '(') === 0) {
				$this->isAggregate = true;
				return $this->isAggregate;
			}
		}
		$this->isAggregate = false;
		return $this->isAggregate;
	}

	protected function setType(mixed $type): void {
		if (is_string($type) && in_array(strtolower($type), self::VALID_TYPES, true)) {
			$this->properties['Type'] = strtolower($type);
		} else {
			throw new \InvalidArgumentException(sprintf(
				'Type must be one of: %s. Given: %s',
				implode(', ', self::VALID_TYPES),
				is_string($type) ? $type : gettype($type)
			));
		}
	}    
}
