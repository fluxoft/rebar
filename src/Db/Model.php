<?php

namespace Fluxoft\Rebar\Db;

/**
 * Class Model
 * @package Fluxoft\Rebar\Db
 */
abstract class Model implements \Iterator {
	protected $modProperties = [];
	/**
	 * $propertyDbMap = [
	 *   '{Property Name}' => [
	 *     'col' => '{database column}',
	 *     'type' => '{column type (PDO type)}',
	 *     'value' => {default value}
	 *   ]
	 * ]
	 *
	 * or
	 *
	 * $propertyDbMap = [
	 *   '{Property Name}' => '{database column}'
	 * ]
	 * The second form will set a default of PDO::PARAM_STR for type and null for value.
	 * @var array
	 */
	protected $propertyDbMap = [];
	protected $dbTable       = '';
	protected $idProperty    = 'ID';

	public function __construct(array $dataRow = []) {
		if (empty($this->propertyDbMap)) {
			throw new \Exception(sprintf('You must specify the db column relationships in propertyDbMap'));
		}
		if (strlen($this->dbTable) === 0) {
			throw new \Exception(sprintf('You must specify the database table in dbTable'));
		}
		foreach ($this->propertyDbMap as $property => &$dbMap) {
			if (!is_array($dbMap)) {
				$dbMap = ['col' => $dbMap, 'type' => \PDO::PARAM_STR, 'value' => null];
			}
		}

		// initialize the properties of this model
		if (empty($dataRow)) {
			// For an empty dataSet, this is a blank object. Set the ID property to 0 to indicate an
			// uninitialized object.
			$this->propertyDbMap[$this->idProperty]['value'] = 0;
		} else {
			/**
			 * Otherwise, use the values from the $dataRow to populate $this->properties using the map
			 * provided in $this->propertyDbMap. The $dataRow can contain a partial $dataRow, but must
			 * contain at least a value for ID. In this case, properties not included in $dataRow are left
			 * as the default value for the property.
			 */
			if (!in_array($this->propertyDbMap[$this->idProperty]['col'], array_keys($dataRow))) {
				throw new \Exception(sprintf(
					'The given dataRow does not include a value for the ID.
					This value is required when a dataRow is given.'
				));
			}
			// Populate the properties with the given values.
			foreach ($this->propertyDbMap as $property => &$dbMap) {
				if (isset($dataRow[$dbMap['col']])) {
					$dbMap['value'] = $dataRow[$dbMap['col']];
				}
			}
		}
	}

	public function GetDbTable() {
		return $this->dbTable;
	}

	public function GetIDProperty() {
		return $this->idProperty;
	}

	public function GetIDColumn() {
		return $this->propertyDbMap[$this->idProperty]['col'];
	}

	public function GetIDType() {
		return $this->propertyDbMap[$this->idProperty]['type'];
	}

	public function GetID() {
		return $this->propertyDbMap[$this->idProperty]['value'];
	}

	public function SetID($id) {
		$this->propertyDbMap[$this->idProperty]['value'] = $id;
	}

	public function GetProperties() {
		return $this->propertyDbMap;
	}

	public function GetModifiedProperties() {
		return $this->modProperties;
	}

	public function __toString() {
		$string = get_class($this) . ": {\n";
		foreach ($this->propertyDbMap as $property => $dbMap) {
			$string .= "  $property ({$dbMap['col']}): " . $this->$property . "\n";
		}
		$string .= "}\n";
		return $string;
	}

	public function __get($key) {
		$fnName = "get$key";
		if (method_exists($this, $fnName)) {
			return $this->$fnName();
		} elseif (isset($this->modProperties[$key])) {
			return $this->modProperties[$key]['value'];
		} elseif (isset($this->propertyDbMap[$key])) {
			return $this->propertyDbMap[$key]['value'];
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}

	public function __set($key, $value) {
		$fnName = "set$key";
		if (method_exists($this, $fnName)) {
			$this->$fnName($value);
		} elseif (isset($this->propertyDbMap[$key])) {
			if ($this->propertyDbMap[$key]['value'] != $value) {
				$this->modProperties[$key]          = $this->propertyDbMap[$key];
				$this->modProperties[$key]['value'] = $value;
			}
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}

	// Iterator interface implementation:
	private $position = 0;

	public function rewind() {
		$this->position = 0;
	}

	public function current() {
		$keys         = array_keys($this->propertyDbMap);
		$propertyName = $keys[$this->position];
		return $this->$propertyName;
	}

	public function key() {
		$keys = array_keys($this->propertyDbMap);
		return $keys[$this->position];
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		return !($this->position > count($this->propertyDbMap) - 1);
	}
}
