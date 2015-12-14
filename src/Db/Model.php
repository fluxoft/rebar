<?php

namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Model as BaseModel;

/**
 * Class Model
 * @package Fluxoft\Rebar\Db
 */
abstract class Model extends BaseModel {
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

	/** @var string The name of the database table that holds these models. */
	protected $dbTable = '';

	/** @var string The name of the property that contains the ID
	 * (used by Mapper to build certain queries) */
	protected $idProperty = 'ID';

	/**
	 * @param array $dataRow
	 * @throws ModelException
	 */
	public function __construct(array $dataRow = []) {
		if (empty($this->propertyDbMap)) {
			throw new ModelException(sprintf('You must specify the db column relationships in propertyDbMap'));
		}
		if (strlen($this->dbTable) === 0) {
			throw new ModelException(sprintf('You must specify the database table in dbTable'));
		}

		foreach ($this->propertyDbMap as $property => &$dbMap) {
			if (!is_array($dbMap)) {
				$dbMap = ['col' => $dbMap, 'type' => \PDO::PARAM_STR, 'value' => null];
			}
			$this->properties[$property] = $dbMap['value'];
			// this is no longer needed
			unset($dbMap['value']);
		}

		// initialize the properties of this model
		if (empty($dataRow)) {
			// For an empty dataSet, this is a blank object. Set the ID property to 0 to indicate an
			// uninitialized object.
			$this->properties[$this->idProperty] = 0;
		} else {
			/**
			 * Otherwise, use the values from the $dataRow to populate $this->properties using the map
			 * provided in $this->propertyDbMap. The $dataRow can contain a partial $dataRow, but must
			 * contain at least a value for ID. In this case, properties not included in $dataRow are left
			 * as the default value for the property.
			 */
			if (!in_array($this->propertyDbMap[$this->idProperty]['col'], array_keys($dataRow))) {
				throw new ModelException(sprintf(
					'The given dataRow does not include a value for the ID.
					This value is required when a dataRow is given.'
				));
			}
			// Populate the properties with the given values.
			foreach ($this->propertyDbMap as $property => &$dbMap) {
				if (isset($dataRow[$dbMap['col']])) {
					if ($dbMap['type'] === 'boolean') {
						$this->properties[$property] = (boolean) $dataRow[$dbMap['col']];
					} else {
						$this->properties[$property] = $dataRow[$dbMap['col']];
					}
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetDbTable() {
		return $this->dbTable;
	}

	/**
	 * @return string
	 */
	public function GetIDProperty() {
		return $this->idProperty;
	}

	/**
	 * @return mixed
	 */
	public function GetIDColumn() {
		return $this->propertyDbMap[$this->idProperty]['col'];
	}

	/**
	 * @return mixed
	 */
	public function GetIDType() {
		return $this->propertyDbMap[$this->idProperty]['type'];
	}

	/**
	 * @return mixed
	 */
	public function GetID() {
		return $this->properties[$this->idProperty];
	}

	/**
	 * @param $id
	 */
	public function SetID($id) {
		$this->properties[$this->idProperty] = $id;
	}
}
