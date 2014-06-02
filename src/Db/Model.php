<?php
namespace Fluxoft\Rebar\Db;

use \Fluxoft\Rebar\Db\Exceptions\ModelException;

abstract class Model implements \Iterator, \ArrayAccess {
	/**
	 * Holds the internal array of property names and values.
	 * @var array $properties
	 */
	protected $properties = array();
	/**
	 * Properties that have been changed from their original values but have not yet been written to the database.
	 * @var array $modProperties
	 */
	protected $modProperties = array();
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
			\Fluxoft\Rebar\Db\Providers\Provider $reader,
			\Fluxoft\Rebar\Db\Providers\Provider $writer,
			$id = 0,
			array $setProperties = array()
	) {
		$this->reader = $reader;
		$this->writer = $writer;
		
		$this->propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$this->dbSelectTable = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;
		
		// sanity check for making new object
		if (
			(count($this->properties) > 0) &&
			(count($this->propertyDbMap) > 0) &&
			(strlen($this->dbTable) > 0)
		) {
			// select map must have the same number of elements as properties
			if ((count($this->properties) == count($this->propertyDbSelectMap))) {
				// If $id is greater than zero and the $setProperties array is zero-length,
				// then get the values for this object's $properties from the $dbSelectTable
				// and set them.
				if (($id > 0) && (count($setProperties) == 0)) {
					$query = 'SELECT ';
					$i = 1;
					foreach($this->properties as $propertyName => $propertyValue) {
						$query .= $this->propertyDbSelectMap[$propertyName];
						if ($i < count($this->properties)) {
							$query .= ', ';
						}
						$i++;
					}
					$query .= ' FROM '.$this->dbSelectTable;
					$query .= ' WHERE '.$this->propertyDbSelectMap[$this->idProperty].' = :id';
		
					$params = array(':id' => $id);
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
				// If $id is 0 and the array is zero-length, then just make sure the $idProperty
				// is set to 0, in case the $properties array in the child class isn't set that way.
				// This becomes important in the Save() function to determine whether a new db
				// record needs to be created.  The other default values
				else {
					$this->properties[$this->idProperty] = 0;
				}
			}
		}
		
		$properties = $this->properties;
		// If $id is zero and the $setProperties array has members,
		// then just initialize the $properties array.
		if (($id > 0) && (count($setProperties) > 0)) {
			if (count($setProperties) == count($this->properties)) {
				foreach($this->properties as $propertyName => $propertyValue) {
					$this->properties[$propertyName] = $setProperties[$propertyName];
				}
			}
		}
	}
	
	/**
	 * Override the magic __set() method to set the values
	 * for members of the $properties array.
	 *  
	 * @param string $var
	 * @param mixed $val
	 */
	public function __set($key, $value) {
		$fnName = "set$key";
		if (method_exists($this,$fnName)) {
			$this->$fnName($value);
		} else if (isset($this->properties[$key])) {
			if ($this->properties[$key] != $value) {
				$this->modProperties[$key] = $value;
			}
		} else {
			throw new \InvalidArgumentExceptionException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}
	/**
	 * Override the magic __get() method to get the value for
	 * the specified member of the $properties array.  If a value
	 * exists in $modProperties, return that one, as that contains
	 * the updated value.  If the requested property does not
	 * exist in either array, try to find a method called
	 * "get[PropertyName].  If found, return the result of that
	 * function.
	 * 
	 * @param string $var
	 */
	public function __get($key) {
		$fnName = "get$key";
		if (method_exists($this,$fnName)) {
			return $this->$fnName();
		} else if (isset($this->modProperties[$key])) {
			return $this->modProperties[$key];
		} else if (isset($this->properties[$key])) {
			return $this->properties[$key];
		} else {
			throw new \InvalidArgumentExceptionException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}
	
	public function __toString() {
		$string = get_class($this) . " object {\n";
		foreach ($this->properties as $key => $value) {
			$string .= "  $key: " . $this->$key . "\n";
		}
		$string .= "}\n";
		return $string;
	}
	
	/**
	 * The Save() method calls create() if the ID is 0 or update() is greater than 0.
	 */
	public function Save() {
		// Empty classes are initialized with ID = 0
		if ($this->properties[$this->idProperty] === 0) {
			echo "creating\n";
			$this->create();
		}
		// unretrievable rows are set up as ID = -1
		else if ($this->properties[$this->idProperty] == -1) {
			// do nothing
		}
		// otherwise, it's an existing ID
		else {
			echo "updating\n";
			$this->update();
		}
	}
	
	/**
	 * Delete a database record
	 * @param int $deleteID The ID to be deleted.
	 */
	public function Delete($deleteID) {
		$query = 'DELETE FROM '.$this->dbTable.' WHERE '.$this->propertyDbMap[$this->idProperty].' = :'.$this->propertyDbMap[$this->idProperty];
		$params = array(':'.$this->propertyDbMap[$this->idProperty] => $deleteID);
		$return = $this->writer->Delete($query, $params);
		$this->properties[$this->idProperty] = 0;
		return $return;
	}
	
	/**
	 * Get a set of objects.
	 *
	 * @param string $orderBy How results should be ordered.  Expects database column(s).
	 * @param string $where Filter clause.
	 * @param string|int $limit How many rows to return.  Default value 'ALL' will return all rows.
	 * @param string $offset How many rows to skip.
	 */
	public function GetAll($where = '', $orderBy = '', $page = 1, $pageSize = '0') {
		$propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$dbSelectTable = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;
		$properties = array_keys($propertyDbSelectMap);
			
		$query = 'SELECT ';
		$i = 1;
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
				$whereColumn = (strpos($whereColumn,' ')) ? substr($whereColumn, 0, strpos($whereColumn,' ')) : $whereColumn;
				$where = str_replace('{'.$propertyName.'}',$whereColumn,$where);
			}
			$query .= ' WHERE '.$where;
		}
		if (strlen($orderBy) > 0) {
			foreach($propertyDbSelectMap as $propertyName => $dbColumn) {
				if (strpos($dbColumn,' ')) {
					$dbColumn = substr($dbColumn,0,strpos($dbColumn,' '));
					$startPos = strpos($dbColumn,' ') + 1;
				}
				$orderBy = str_replace('{'.$propertyName.'}',$dbColumn,$orderBy);
			}
			$query .= ' ORDER BY '.$orderBy;
		}
		if ($pageSize > 0) {
			$limit = $pageSize;
			$offset = (($page - 1) * $pageSize);
			$query .= ' LIMIT '.$limit.' OFFSET '.$offset;
		}
		$returnSet = $this->reader->SelectSet($query);
		$return = $this->returnObjectSet($returnSet);
		return $return;
	}
	
	// Get a count of the number of rows that would be returned
	// if this were a query.  The query is constructed as in
	// GetAll(), but only count(*) is fetched and returned as a
	// scalar.  This saves on the data interchange of actually having
	// to get the row data, improving performance for simple row
	// count operations necessary for pagination, etc.
	
	public function Count($where = '', $whereParams = array()) {
		$propertyDbSelectMap = (count($this->propertyDbSelectMap)) ? $this->propertyDbSelectMap : $this->propertyDbMap;
		$dbSelectTable = (strlen($this->dbSelectTable)) ? $this->dbSelectTable : $this->dbTable;
			
		$query = 'SELECT count(*) count FROM ' . $dbSelectTable;
		if (strlen($where) > 0) {
			foreach($propertyDbSelectMap as $propertyName => $dbColumn) {
				$where = str_replace('{'.$propertyName.'}',$dbColumn,$where);
			}
			$query .= ' WHERE '.$where;
		}
		$query .= ';';
		$params = $whereParams;
		return $this->reader->SelectValue($query, $params);
	}
	
	/**
	 * The Create() method inserts a new record into the database and updates
	 * the object's ID property with the value of the new unique ID.
	 */
	private function create() {
		$query = 'INSERT INTO '.$this->dbTable.' (';
		$i = 2;
		foreach($this->propertyDbMap as $propertyName => $dbColumn) {
			if ($propertyName != $this->idProperty) {
				$query .= $dbColumn;
				if ($i < count($this->propertyDbMap)) {
					$query .= ', ';
				}
				$i++;
			}
		}
		$query .= ') VALUES (';
		$i = 2;
		foreach($this->propertyDbMap as $propertyName => $dbColumn) {
			if ($propertyName != $this->idProperty) {
				$query .= ':'.$dbColumn;
				if ($i < count($this->propertyDbMap)) {
					$query .= ', ';
				}
				$i++;
			}
		}
		$query .= ')';
		$params = array();
		foreach($this->propertyDbMap as $propertyName => $dbColumn) {
			if ($propertyName != $this->idProperty) {
				$params[$dbColumn] = $this->modProperties[$propertyName];
			}
		}
		try {
			$this->properties[$this->idProperty] = $this->writer->Insert($query, $params, $this->idSequence);
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
		$query = 'UPDATE '.$this->dbTable.' SET ';
		$params = array();
		$i = 1;
		$run = true;
		// if there are no values set in $modProperties, there is no point to any of this
		if (count($this->modProperties) > 0) {
			foreach ($this->modProperties as $propertyName => $value) {
				// do not attempt to update the $idProperty of a model
				if ($propertyName === $this->idProperty) {
					// if this was the only member of the array, skip running the query altogether
					$run = false;
				} else {
					$dbColumn = $this->propertyDbMap[$propertyName];
					$query .= $dbColumn.' = :'.$dbColumn;
					if ($i < count($this->modProperties)) {
						$query .= ', ';
					}
					$params[':'.$dbColumn] = $value;
					$run = true;
				}
				$i++;
			}
			$query .= ' WHERE '.$this->propertyDbMap[$this->idProperty].' = :'.$this->propertyDbMap[$this->idProperty];
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
	
	protected function assignProperty($property, $value) {
		$this->modProperties[$property] = $value;
	}
	protected function retrieveProperty($property) {
		return isset($this->modProperties[$property]) ? $this->modProperties[$property] : $this->properties[$property];
	}
	
	private function assignProperties(array $dbArray) {
		foreach($dbArray as $dataKey => $dataValue) {
			foreach($this->propertyDbSelectMap as $propertyName => $dbColumn) {
				$startPos = 0;
				if (strpos($dbColumn,'.')) $startPos = strpos($dbColumn,'.') + 1;
				if (strpos($dbColumn,' ')) $startPos = strpos($dbColumn,' ') + 1;
				if ($dataKey == substr($dbColumn,$startPos)) {
					// If a null value was returned, the property will appear to be unset.
					// Therefore, nulls must be set to empty strings here.
					if (!isset($dataValue)) $dataValue = '';
					$this->properties[$propertyName] = $dataValue;
				}
			}
		}
	}
	
	private function returnObjectSet($dataSet) {
		$return = array();
		$className = get_class($this);
		foreach($dataSet as $row) {
			$thisObj = new $className($this->reader, $this->writer);
			$thisObj->assignProperties($row);
			$return[] = $thisObj;
		}
		return $return;
	}
	
	// Iterator interface implementation.
	private $position = 0;
	public function rewind() {
		$this->position = 0;
	}
	public function current() {
		$keys = array_keys($this->properties);
		$propertyName = $keys[$this->position];
		return $this->$propertyName;
	}
	public function key() {
		$keys = array_keys($this->properties);
		return $keys[$this->position];
	}
	public function next() {
		++$this->position;
	}
	public function valid() {
		if ($this->position > count($this->properties)-1) {
			return false;
		}
		else return true;
	}
	
	// ArrayAccess implementation.
	public function offsetExists($offset) {
		return isset($this->properties[$offset]);
	}
	public function offsetGet($offset) {
		return $this->$offset;
	}
	public function offsetSet($offset, $value) {
		$this->$offset = value;
	}
	public function offsetUnset($offset) {
		$this->properties[$offset] = null;
	}
}