<?php

namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Db\Exceptions\ModelException;
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
	 *     'type' => '{column type (PDO type)}'
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
	protected $idProperty = 'Id';

	/**
	 * @param array $properties
	 * @throws ModelException
	 */
	public function __construct(array $properties = []) {
		if (empty($this->propertyDbMap)) {
			throw new ModelException('You must specify the db column relationships in propertyDbMap');
		}
		if (strlen($this->dbTable) === 0) {
			throw new ModelException('You must specify the database table in dbTable');
		}
		if (!isset($this->propertyDbMap[$this->idProperty])) {
			throw new ModelException('The idProperty must be present in propertyDbMap');
		}

		foreach ($this->propertyDbMap as $property => &$dbMap) {
			if (!is_array($dbMap)) {
				$dbMap = ['col' => $dbMap, 'type' => \PDO::PARAM_STR, 'value' => null];
			}
			$this->properties[$property] = isset($dbMap['value']) ?? null;
			// this is no longer needed
			unset($dbMap['value']);
		}

		parent::__construct($properties);

		if (!isset($properties[$this->idProperty])) {
			$this->properties[$this->idProperty] = 0;
		}
	}

	/**
	 * @return array
	 */
	public function GetPropertyDbMap() {
		return $this->propertyDbMap;
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
	public function GetIdProperty() {
		return $this->idProperty;
	}

	/**
	 * @return mixed
	 */
	public function GetIdColumn() {
		return $this->propertyDbMap[$this->idProperty]['col'];
	}

	/**
	 * @return mixed
	 */
	public function GetIdType() {
		return $this->propertyDbMap[$this->idProperty]['type'];
	}

	/**
	 * @return mixed
	 */
	public function GetId() {
		return $this->properties[$this->idProperty];
	}

	/**
	 * @param $id
	 */
	public function SetId($id) {
		$this->properties[$this->idProperty] = $id;
	}
}
