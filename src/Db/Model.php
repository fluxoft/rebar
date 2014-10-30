<?php
namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Db\Exceptions\ModelException;
use \Fluxoft\Rebar\Model as BaseModel;

abstract class Model extends BaseModel {
	/**
	 * The name of the property to be used as the unique ID.
	 * @var string $idProperty
	 */
	protected $idProperty = 'ID';
	/**
	 * The name of the database sequence used for the unique ID.
	 * @var string $idSequence
	 */
	protected $idSequence = null;
	/**
	 * Relationships between property names and database column names.
	 * @var array $propertyDbMap
	 */
	protected $propertyDbMap = array();
	/**
	 * If the model needs to select from joined tables, this array stores those aliased table names.
	 * @var array $propertyDbSelectMap
	 */
	protected $propertyDbSelectMap = array();
	/**
	 * The name of the table represented by this model.
	 * @var string $dbTable
	 */
	protected $dbTable = '';
	/**
	 * If the model is selecting from joined tables, this should define the join syntax.
	 * @var string $dbSelectTable
	 */
	protected $dbSelectTable = '';
	/**
	 * @var \Fluxoft\Rebar\Db\ModelFactory
	 */
	protected $factory;
	/**
	 * The provider used to read from the database.
	 * @var \Fluxoft\Rebar\Db\Providers\Provider
	 */
	protected $reader = null;
	/**
	 * The provider used to write to the database.
	 * @var \Fluxoft\Rebar\Db\Providers\Provider
	 */
	protected $writer = null;

	public function __construct(
		ModelFactory $factory,
		$id = 0,
		array $setProperties = array()
	) {
		// sanity check for making new object
		if (count($this->propertyDbMap) === 0 || strlen($this->dbTable) === 0) {
			throw new ModelException('Db Model must include propertyDbMap and dbTable properties.');
		}
		// if only propertyDbMap was configured, use the keys to make a starter properties array
		if (count($this->properties) === 0) {
			$this->properties = array_fill_keys(array_keys($this->propertyDbMap), '');
		}

		// if select-specific properties were not set, copy them from the generics
		$this->propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$this->dbSelectTable       = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;

		// select map must have the same number of elements as properties
		if ((count($this->properties) !== count($this->propertyDbSelectMap))) {
			throw new ModelException(sprintf('Model misconfiguration error. Property array mismatch.'));
		}

		$this->factory = $factory;
		$this->reader  = $factory->Reader;
		$this->writer  = $factory->Writer;

		if (($id > 0)) {
			if (count($setProperties) > 0) {
				// If $id is not zero and the $setProperties array has members,
				// then just initialize the $properties array.
				parent::__construct($setProperties);
			} else {
				// If $id is greater than zero and the $setProperties array is zero-length,
				// then get the values for this object's $properties from the $dbSelectTable
				// and set them.
				$query = 'SELECT ';
				$i     = 1;
				foreach($this->properties as $propertyName => $propertyValue) {
					$query .= $this->propertyDbSelectMap[$propertyName];
					if ($i < count($this->properties)) {
						$query .= ', ';
					}
					$i++;
				}
				$query .= ' FROM '.$this->dbSelectTable;
				$query .= ' WHERE '.$this->propertyDbSelectMap[$this->idProperty].' = :id';

				$params    = array(':id' => $id);
				$returnSet = $this->reader->SelectSet($query, $params);

				if ($returnSet) {
					//foreach($returnSet[0] as $dataKey => $dataValue) {
					foreach($returnSet as $row) {
						$this->assignProperties($row);
					}
				} else {
					$this->properties[$this->idProperty] = -1;
				}
			}
		} else {
			// If $id is 0 (or less), then just make sure the $idProperty
			// is set to 0, in case the $properties array in the child class isn't set that way.
			// This becomes important in the Save() function to determine whether a new db
			// record needs to be created.  The other default values from $properties are preserved.
			$this->properties[$this->idProperty] = 0;
		}
	}

	/**
	 * The Save() method calls create() if the ID is 0 or update() if greater than 0.
	 */
	public function Save() {
		if ($this->properties[$this->idProperty] === 0) {
			// Empty classes are initialized with ID = 0
			$this->create();
		} elseif ($this->properties[$this->idProperty] > 0) {
			// Valid ID
			$this->update();
		}
	}

	/**
	 * Delete a database record
	 * @param $deleteID
	 * @return bool
	 */
	public function Delete($deleteID) {
		$query                               = 'DELETE FROM ' . $this->dbTable . ' WHERE ' .
			$this->propertyDbMap[$this->idProperty].' = :'.$this->propertyDbMap[$this->idProperty];
		$params                              = array(':'.$this->propertyDbMap[$this->idProperty] => $deleteID);
		$return                              = $this->writer->Delete($query, $params);
		$this->properties[$this->idProperty] = 0;
		return $return;
	}

	/**
	 * Get a set of objects.
	 * @param string $where    Filter clause
	 * @param string $orderBy  Sort order.
	 * @param int $page        Which page of results to return.
	 * @param string $pageSize Number of results per page.
	 * @return array
	 */
	public function GetSet($where = '', $orderBy = '', $page = 1, $pageSize = '0') {
		$propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$dbSelectTable       = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;
		$properties          = array_keys($propertyDbSelectMap);

		$query = 'SELECT ';
		$i     = 1;
		foreach($properties as $propertyName) {
			$query .= $propertyDbSelectMap[$propertyName];
			if ($i < count($properties)) {
				$query .= ', ';
			}
			$i++;
		}
		$query .= ' FROM '.$dbSelectTable;
		if (strlen($where) > 0) {
			foreach($propertyDbSelectMap as $propertyName => $whereColumn) {
				$whereColumn = (strpos($whereColumn, ' ')) ?
					substr($whereColumn, 0, strpos($whereColumn, ' ')) :
					$whereColumn;
				$where       = str_replace('{'.$propertyName.'}', $whereColumn, $where);
			}
			$query .= ' WHERE '.$where;
		}
		if (strlen($orderBy) > 0) {
			foreach($propertyDbSelectMap as $propertyName => $dbColumn) {
				if (strpos($dbColumn, ' ')) {
					$dbColumn = substr($dbColumn, 0, strpos($dbColumn, ' '));
					// $startPos = strpos($dbColumn, ' ') + 1;
				}
				$orderBy = str_replace('{'.$propertyName.'}', $dbColumn, $orderBy);
			}
			$query .= ' ORDER BY '.$orderBy;
		}
		if ($pageSize > 0) {
			$limit  = $pageSize;
			$offset = (($page - 1) * $pageSize);
			$query .= ' LIMIT '.$limit.' OFFSET '.$offset;
		}
		return $this->GetFromDataSet($this->reader->SelectSet($query));
	}

	public function GetFromDataRow($dataRow) {
		$className = get_class($this);
		/** @var \Fluxoft\Rebar\Db\Model $return */
		$return = new $className($this->factory);
		$return->assignProperties($dataRow);
		return $return;
	}

	public function GetFromDataSet($dataSet) {
		$return = array();
		foreach($dataSet as $row) {
			$return[] = $this->GetFromDataRow($row);
		}
		return $return;
	}

	/**
	 * Get a count of the number of rows that would be returned
	 * if this were a query.  The query is constructed as in
	 * GetAll(), but only count(*) is fetched and returned as a
	 * scalar.  This saves on the data interchange of actually having
	 * to get the row data, improving performance for simple row
	 * count operations necessary for pagination, etc.
	 * @param string $where
	 * @param array $whereParams
	 * @return mixed
	 */
	public function Count($where = '', $whereParams = array()) {
		$propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$dbSelectTable       = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;

		$query = 'SELECT count(*) count FROM ' . $dbSelectTable;
		if (strlen($where) > 0) {
			foreach($propertyDbSelectMap as $propertyName => $dbColumn) {
				$where = str_replace('{'.$propertyName.'}', $dbColumn, $where);
			}
			$query .= ' WHERE '.$where;
		}
		$query .= ';';
		$params = $whereParams;
		return $this->reader->SelectValue($query, $params);
	}

	protected function assignProperty($property, $value) {
		$this->modProperties[$property] = $value;
	}
	protected function retrieveProperty($property) {
		return isset($this->modProperties[$property]) ? $this->modProperties[$property] : $this->properties[$property];
	}

	private function assignProperties(array $dbArray) {
		foreach($dbArray as $dataKey => $dataValue) {
			foreach($this->propertyDbSelectMap as $propertyName => $dbColumn) {
				$dataKeyDef = $dbColumn;
				if (strpos($dbColumn, '.')) {
					$dataKeyDef = substr($dbColumn, strpos($dbColumn, '.') + 1);
				}
				if (strrpos($dbColumn, ' ')) {
					$dataKeyDef = substr($dbColumn, strrpos($dbColumn, ' ') + 1);
				}
				if ($dataKey === $dataKeyDef) {
					// If a null value was returned, the property will appear to be unset.
					// Therefore, nulls must be set to empty strings here.
					if (!isset($dataValue)) {
						$dataValue = '';
					}
					$this->properties[$propertyName] = $dataValue;
				}
			}
		}
	}

	/**
	 * The Create() method inserts a new record into the database and updates
	 * the object's ID property with the value of the new unique ID.
	 */
	private function create() {
		$insertParams = array();
		foreach($this->modProperties as $propertyName => $propertyValue) {
			$insertParams[$this->propertyDbMap[$propertyName]] = $propertyValue;
		}
		$query = 'INSERT INTO '.$this->dbTable.' ('.
			implode(',', array_keys($insertParams)).
			') VALUES (:'.implode(',:', array_keys($insertParams)).
			')';
		try {
			$this->properties[$this->idProperty] = $this->writer->Insert($query, $insertParams, $this->idSequence);
			// copy $modProperties to $properties and then reset $modProperties
			foreach ($this->modProperties as $property => $value) {
				$this->properties[$property] = $value;
			}
			$this->modProperties = array();
		} catch (\PDOException $e) {
			throw $e;
		}
	}
	/**
	 * The Update() function updates the values in the database for record with unique ID
	 * matching the ID property with the values of the $modProperties array.
	 */
	private function update() {
		$query  = 'UPDATE '.$this->dbTable.' SET ';
		$params = array();
		$i      = 1;
		$run    = true;
		// if there are no values set in $modProperties, there is no point to any of this
		if (count($this->modProperties) > 0) {
			foreach ($this->modProperties as $propertyName => $value) {
				// do not attempt to update the $idProperty of a model
				if ($propertyName === $this->idProperty) {
					// if this was the only member of the array, skip running the query altogether
					$run = false;
				} else {
					$dbColumn = $this->propertyDbMap[$propertyName];
					$query   .= $dbColumn.' = :'.$dbColumn;
					if ($i < count($this->modProperties)) {
						$query .= ', ';
					}
					$params[':'.$dbColumn] = $value;
					$run                   = true;
				}
				$i++;
			}
			$query                                              .=
				' WHERE '.$this->propertyDbMap[$this->idProperty].' = :'.$this->propertyDbMap[$this->idProperty];
			$params[':'.$this->propertyDbMap[$this->idProperty]] = $this->properties[$this->idProperty];
			if ($run) {
				try {
					$this->writer->Update($query, $params);
					// copy $modProperties to $properties and then reset $modProperties
					foreach ($this->modProperties as $property => $value) {
						$this->properties[$property] = $value;
					}
					$this->modProperties = array();
				} catch (\PDOException $e) {
					throw $e;
				}
			}
		}
	}
}