<?php

namespace Fluxoft\Rebar\Db\Mappers;

use Fluxoft\Rebar\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Db\Exceptions\MapperException;
use Fluxoft\Rebar\Db\Filter;
use Fluxoft\Rebar\Model;
use Fluxoft\Rebar\Db\MapperFactory;
use PDO;
use Fluxoft\Rebar\Db\Property;
use Fluxoft\Rebar\Db\Join;

/**
 * Class Generic
 * This class is a generic database mapper, meant to be extended by a specific mapper for a
 * specific database server, e.g. MySQLMapper, SQLiteMapper, etc. However, for very simple
 * applications, this class can be used directly.
 *
 * There should be a mapper for each model in the application. The mapper is responsible for
 * CRUD operations on the model. Generally speaking, the mapper should be the only place in
 * the application where SQL is written (if the automatically-generated SQL isn't sufficient).
 *
 * @package Fluxoft\Rebar\Db\Mappers
 */
abstract class GenericSql implements MapperInterface {
	/** @var MapperFactory */
	protected MapperFactory $mapperFactory;
	/** @var Model */
	protected Model $model;
	/** @var PDO */
	protected PDO $reader;
	/** @var PDO */
	protected PDO $writer;

	/**
	 * The following can be overridden in the extending class if the SQL needs to be customized.
	 * For example, say you have a table called "users" with columns "id", "username", and "password".
	 * The default SQL generated by the Generic would be:
	 * SELECT `users`.`id` AS `Id`, `users`.`username` AS `Username`, `users`.`password` AS `Password` FROM `users`
	 * This SQL would be generated by the getSelect() method.
	 * If, however, your model has a property called NumberOfLogins, which is calculated by a window function
	 * that uses a JOIN to user_logins, you would override the $selectSql property like so:
	 * protected string $selectSql =
	 *	  'SELECT
	 *	  `users`.`id` AS `Id`,
	 *	  `users`.`username` AS `Username`,
	 *	  `users`.`password` AS `Password`,
	 *	  COUNT(`user_logins`.`id`) OVER (PARTITION BY `users`.`id`) AS `NumberOfLogins`
	 *	  FROM `users`
	 *	  LEFT JOIN `user_logins` ON `users`.`id` = `user_logins`.`user_id`';
	 * This would then be used in the getSelect() method.
	 */
	/** @var string */
	protected string $selectSql;

	/**
	 * The following properties should be overridden in the extending class.
	 * For example in a UserMapper class:
	 * protected string $dbTable = 'users';
	 * protected string $idProperty = 'Id';
	 * protected array $propertyDbMap = [
	 *	 'Id' => 'id',
	 *	 'Username' => 'username',
	 *	 'Password' => 'password'
	 * ];
	 */
	protected string $dbTable;
	protected string $idProperty;
	protected array $propertyDbMap;
	protected array $joins;

	/**
	 * Generic constructor.
	 * @param MapperFactory $mapperFactory
	 * @param Model $model
	 * @param PDO $reader
	 * @param PDO|null $writer
	 */
	public function __construct(
		MapperFactory $mapperFactory,
		Model		 $model,
		PDO		   $reader,
		PDO		   $writer = null
	) {
		$this->mapperFactory = $mapperFactory;
		$this->model         = $model;
		$this->reader        = $reader;
		$this->writer        = $writer ?? $reader;

		foreach ($this->propertyDbMap as $property => &$dbMap) {
			if ($dbMap instanceof Property) {
				continue;
			}
			if (is_string($dbMap)) {
				$this->propertyDbMap[$property] = new Property($dbMap, 'string');
			} elseif (is_array($dbMap)) {
				$column                         = $dbMap['column'] ?? $property;
				$type                           = $dbMap['type'] ?? 'string';
				$this->propertyDbMap[$property] = new Property($column, $type);
			} else {
				error_log("Warning: Legacy property definition for $property. Please migrate to the new format.");
				throw new \InvalidArgumentException("Invalid property definition for $property.");
			}
		}
	}

	public function GetNew(): Model {
		return clone $this->model;
	}

	/**
	 * @return mixed
	 */
	public function GetId() {
		return $this->model->GetProperties()[$this->idProperty] ?? null;
	}

	/**
	 * @param  int $id
	 * @return Model|null
	 * @throws MapperException
	 */
	public function GetOneById(int $id): ?Model {
		$select = $this->getSelect(
			[new Filter($this->propertyDbMap[$this->idProperty]->Column, '=', $id)]
		);

		$stmt = $this->reader->prepare($select['sql']);
		$stmt->execute($select['params']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row) {
			return $this->getModel($row);
		} else {
			return null;
		}
	}

	/**
	 * @param array $filters
	 * @return Model|null
	 * @throws MapperException
	 */
	public function GetOne(array $filters): ?Model {
		$set = $this->GetSet($filters, [], 1, 1);
		if (count($set) > 0) {
			return $set[0];
		} else {
			return null;
		}
	}

	/**
	 * @param array $filters
	 * @param array $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return Model[]
	 * @throws MapperException
	 */
	public function GetSet(
		array $filters = [],
		array $sort = [],
		int   $page = 1,
		int   $pageSize = 0
	): array {
		$select = $this->getSelect($filters, $sort, $page, $pageSize);
		$stmt   = $this->reader->prepare($select['sql']);
		$stmt->execute($select['params']);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $this->getModelSet($rows);
	}

	/**
	 * @param array $filters
	 * @return int
	 */
	public function Count(array $filters = []): int {
		$count = $this->countSelect($filters);
		$stmt  = $this->reader->prepare($count['sql']);
		$stmt->execute($count['params']);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return (int) $row['count'];
	}

	/**
	 * @param Model $model
	 */
	public function Delete(Model &$model): void {
  // Updated parameter type
		$sql  = 'DELETE FROM '.$this->quoteIdentifier($this->dbTable).' WHERE '.
			$this->quoteIdentifier($this->propertyDbMap[$this->idProperty]->Column).' = :id';
		$stmt = $this->writer->prepare($sql);
		$stmt->execute([$this->propertyDbMap[$this->idProperty]->Column => $model->GetId()]);
		$model = null;
	}

	/**
	 * @param int $id
	 * @throws MapperException
	 */
	public function DeleteById(int $id): void {
		$model = $this->GetOneById($id);
		if ($model) {
			$this->Delete($model);
		}
	}

	/**
	 * Save the model to the database.
	 *
	 * @param Model $model
	 * @throws InvalidModelException
	 * @throws Exception
	 */
	public function Save(Model $model): void {
		if (!$model->IsValid()) {
			throw new InvalidModelException('Model failed validation check.');
		}

		if ($model->GetId() === null || $model->GetId() === 0) {
			// ID is null or 0, indicating a new record
			$this->create($model);
		} else {
			// Existing record, perform update
			$this->update($model);
		}
	}


	/**
	 * @param array $filters
	 * @throws MapperException
	 */
	public function DeleteOneWhere(array $filters): void {
		$model = $this->GetOne($filters);
		if ($model) {
			$this->Delete($model);
		}
	}

	/**
	 * Format an identifier for use in SQL
	 * This method can be overridden in the extending class if the database server requires
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteIdentifier(string $identifier): string {
		return $identifier;
	}

	protected function formatValueForInsert(string $type, mixed $value): mixed {
		// Handle DateTime objects
		if ($value instanceof \DateTime) {
			switch ($type) {
				case 'datetime':
					return $value->format('Y-m-d H:i:s');
				case 'date':
					return $value->format('Y-m-d');
				case 'time':
					return $value->format('H:i:s');
				default:
					throw new \InvalidArgumentException('Cannot format DateTime object as type: '.$type);
			}
		}
		// Default behavior: return the value as-is
		return $value;
	}

	/**
	 * Create a new record in the database.
	 *
	 * @param Model $model
	 * @throws Exception
	 */
	protected function create(Model $model): void {
		// Merge properties and modified properties for insertion
		$merged = array_replace_recursive(
			$model->GetProperties(),
			$model->GetModifiedProperties()
		);

		$cols   = [];
		$values = [];
		foreach ($merged as $property => $value) {
			if ($property !== $this->idProperty &&
				!is_null($value) &&
				isset($this->propertyDbMap[$property]) &&
				$this->propertyDbMap[$property]->IsWriteable
			) {
				$cols[]                                          = $this->propertyDbMap[$property]->Column;
				$values[$this->propertyDbMap[$property]->Column] =
					$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);
			}
		}

		// Build SQL
		$sql = "INSERT INTO " . $this->quoteIdentifier($this->dbTable) . " (" .
			implode(', ', array_map(fn($col) => $this->quoteIdentifier($col), $cols)) .
			") VALUES (" .
			implode(', ', array_map(fn($col) => ":$col", $cols)) .
			")";

		// Execute query
		$stmt = $this->writer->prepare($sql);
		$stmt->execute($values);

		// Set the model's ID
		$model[$this->idProperty] = $this->writer->lastInsertId();
	}

	/**
	 * Update an existing record in the database.
	 *
	 * @param Model $model
	 * @throws Exception
	 */
	protected function update(Model $model): void {
		// Get modified properties
		$modified = $model->GetModifiedProperties();
		$values   = [];

		$cols = [];
		foreach ($modified as $property => $value) {
			if (isset($this->propertyDbMap[$property]) &&
				$this->propertyDbMap[$property]->IsWriteable
			) {
				$cols[]            = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) . " = :$property";
				$values[$property] =
					$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);
			}
		}

		if (!empty($cols)) {
			// Add ID to the query for WHERE clause
			$idColumn          = $this->propertyDbMap[$this->idProperty]->Column;
			$values[$idColumn] = $model->GetId();

			// Build SQL
			$sql = "UPDATE " . $this->quoteIdentifier($this->dbTable) . " SET " .
				implode(', ', $cols) .
				" WHERE " . $this->quoteIdentifier($idColumn) . " = :$idColumn";

			// Execute query
			$stmt = $this->writer->prepare($sql);
			$stmt->execute($values);
		}
	}


	/**
	 * @param Filter[] $filters Array of Filter objects
	 * @param array $sort Array of property names to sort by in the order they should be applied,
	 *					e.g. ['Name', 'ID DESC']
	 * @param int $page
	 * @param int $pageSize
	 * @return array{sql: string, params: array}
	 * @throws MapperException
	 */
	protected function getSelect(
		array $filters = [],
		array $sort = [],
		int $page = 1,
		int $pageSize = 0
	): array {
		// Validate pagination parameters
		if ($page < 1) {
			throw new \InvalidArgumentException('Page number must be at least 1.');
		}
		if ($pageSize < 0) {
			throw new \InvalidArgumentException('Page size must be a positive integer.');
		}

		if (!isset($this->selectSql)) {
			$fields = [];
			foreach ($this->propertyDbMap as $property => $propertyObject) {
				$fields[] = 
					$this->quoteIdentifier($this->dbTable) . '.' . $this->quoteIdentifier($propertyObject->Column) .
					" AS {$this->quoteIdentifier($property)}";
			}
			$this->selectSql = 'SELECT '.implode(', ', $fields).' FROM '.$this->quoteIdentifier($this->dbTable);

			// Add JOIN clauses
			$this->selectSql .= ' ' . $this->buildJoins();
		}

		$sql    = $this->selectSql;
		$filter = $this->getFilter($filters);
		$params = [];
		if (!empty($filter['sql'])) {
			$sql   .= $filter['sql'];
			$params = $filter['params'];
		}

		// Apply grouping
		$sql = $this->applyGrouping($sql, $filters);

		// Apply sorting
		$sql = $this->applySorting($sql, $sort);

		// Apply pagination
		$sql = $this->applyPagination($sql, $page, $pageSize);

		return [
			'sql' => $sql,
			'params' => $params
		];
	}

	/**
	 * Determine if there are aggregates in the SELECT clause or filters.
	 *
	 * @param array $filters
	 * @return bool
	 */
	protected function hasAggregatesInSelectOrFilters(array $filters): bool {
		// Check for aggregates in the SELECT clause
		foreach ($this->propertyDbMap as $property => $propertyObject) {
			if ($propertyObject->IsAggregate) {
				return true;
			}
		}
	
		// Check for filters applied to aggregate properties
		foreach ($filters as $filter) {
			if (isset($this->propertyDbMap[$filter->Property]) &&
				$this->propertyDbMap[$filter->Property]->IsAggregate) {
				return true;
			}
		}
	
		return false;
	}	
	
	/**
	 * Add a GROUP BY clause to the SQL statement if there are aggregates in the select or filters.
	 */
	protected function applyGrouping(string $sql): string {
		// Start with propertyDbMap columns
		$groupByColumns = array_map(
			fn($property) => $this->quoteIdentifier($this->dbTable) . '.' . $this->quoteIdentifier($property->Column),
			array_filter($this->propertyDbMap, fn($property) => !$property->IsAggregate)
		);
	
		// Add non-aggregate columns from JOINs
		foreach ($this->joins as $join) {
			foreach ($this->propertyDbMap as $property => $propertyObject) {
				// Check if the property comes from the JOINed table
				if (strpos($propertyObject->Column, $join->Table . '.') === 0 && !$propertyObject->IsAggregate) {
					$groupByColumns[] = $this->quoteIdentifier($propertyObject->Column);
				}
			}
		}

		// If there are grouping columns, append the GROUP BY clause
		if (!empty($groupByColumns)) {
			$sql .= ' GROUP BY ' . implode(', ', $groupByColumns);
		}

		return $sql;
	}	

	/**
	 * Apply sorting to a SQL statement.
	 * @param string $sql
	 * @param array $sort
	 * @return string
	 */
	protected function applySorting(string $sql, array $sort): string {
		if (empty($sort)) {
			return $sql;
		}

		$orderBy = [];
		foreach ($sort as $item) {
			$itemBits = explode(' ', $item);
			$field    = $itemBits[0];
			$order    = (isset($itemBits[1]) && strtolower($itemBits[1]) === 'desc') ? 'DESC' : 'ASC';
			if (array_key_exists($field, $this->model->GetProperties())) {
				$orderBy[] = $this->quoteIdentifier($field) . " $order";
			}
		}

		if (!empty($orderBy)) {
			$sql .= ' ORDER BY ' . implode(', ', $orderBy);
		}

		return $sql;
	}

	/**
	 * Used to apply pagination to a SQL statement.
	 * @param string $sql
	 * @param int $page
	 * @param int $pageSize
	 * @return string
	 */
	protected function applyPagination(string $sql, int $page, int $pageSize): string {
		if ($pageSize > 0) {
			$offset = $pageSize * ($page - 1);
			$sql   .= " LIMIT $pageSize OFFSET $offset";
		}
		return $sql;
	}

	/**
	 * Build the JOIN clauses for the SQL statement.
	 * @return string
	 */
	protected function buildJoins(): string {
		$joinClauses = [];
		foreach ($this->joins as $join) {
			if (!$join instanceof Join) {
				throw new \LogicException('Invalid join definition. Expected instance of Join.');
			}
			$alias         = $join->Alias ? " AS {$this->quoteIdentifier($join->Alias)}" : '';
			$joinClauses[] = "{$join->Type} JOIN {$this->quoteIdentifier($join->Table)} ON {$join->On}{$alias}";
		}
		return implode(' ', $joinClauses);
	}

	/**
	 * @throws MapperException
	 */
	protected function countSelect(array $filters = []): array {
		// Start building the COUNT SQL
		$sql = 'SELECT COUNT(*) AS count FROM ' . $this->quoteIdentifier($this->dbTable);

		// Include JOIN clauses if they exist
		if (!empty($this->joins)) {
			$sql .= ' ' . $this->buildJoins();
		}

		// Apply filters (WHERE and HAVING clauses)
		$filter = $this->getFilter($filters);
		$params = [];
		if (!empty($filter['sql'])) {
			$sql   .= $filter['sql'];
			$params = $filter['params'];
		}

		return [
			'sql'   => $sql,
			'params' => $params
		];
	}

	/**
	 * @param Filter[] $filters
	 * @return array{sql: string, params: array}
	 *	sql: The WHERE clause of the SQL statement
	 *	params: The parameters to bind to the SQL statement
	 * @throws MapperException
	 */
	protected function getFilter(array $filters = []): array {
		$properties    = $this->model->GetProperties();
		$params        = [];
		$whereFilters  = [];
		$havingFilters = [];
		$filterString  = '';

		// Apply filters, if provided.
		if (!empty($filters)) {
			// Get the operator and value for each filter.
			foreach ($filters as $filter) {
				switch (strtoupper($filter->Operator)) {
					case 'IN':
						$placeholders = [];
						foreach ($filter->Value as $phKey => $value) {
							$placeholder    = ":{$filter->Property}_$phKey";
							$placeholders[] = $placeholder;

							$params[$placeholder] = $value;
						}
						$filterSql = "$filter->Property IN (".implode(', ', $placeholders).")";
						break;
					case 'BETWEEN':
						$filterSql = "$filter->Property BETWEEN :{$filter->Property}_min AND :{$filter->Property}_max";

						$params["{$filter->Property}_min"] = $filter->Value[0];
						$params["{$filter->Property}_max"] = $filter->Value[1];
						break;
					default:
						$filterSql = "$filter->Operator :{$filter->Property}";

						$params[$filter->Property] = $filter->Value;
						break;
				}

				// If this property is in the propertyDbMap, this should be part of the WHERE clause
				if (isset($this->propertyDbMap[$filter->Property])) {
					$whereFilters[] = $this->quoteIdentifier($this->dbTable) . '.' .
						$this->quoteIdentifier($this->propertyDbMap[$filter->Property]->Column) . " $filterSql";
				}
				// If not in propertyDbMap but in the model properties, this may be a calculated field, so add to HAVING
				elseif (isset($properties[$filter->Property])) {
					$havingFilters[] = "{$this->quoteIdentifier($filter->Property)} $filterSql";
				}
				// If not in either, this is an error.
				else {
					throw new MapperException(sprintf(
						'Trying to filter on a non-property: %s',
						$filter->Property
					));
				}
			}
		}
		if (!empty($whereFilters)) {
			$filterString = ' WHERE '.implode(' AND ', $whereFilters);
		}
		if (!empty($havingFilters)) {
			$filterString .= ' HAVING '.implode(' AND ', $havingFilters);
		}
		return [
			'sql'	=> $filterString,
			'params' => $params
		];
	}

	/**
	 * @param array $initialProperties
	 * @return Model // Updated return type
	 */
	protected function getModel(array $initialProperties = []): Model {
		$model = $this->GetNew();
		$model->InitializeProperties($initialProperties);
		return $model;
	}

	/**
	 * @param array $initialPropertiesSet
	 * @return Model[] // Updated return type
	 */
	protected function getModelSet(array $initialPropertiesSet): array {
		$models = [];
		foreach ($initialPropertiesSet as $initialProperties) {
			$models[] = $this->getModel($initialProperties);
		}
		return $models;
	}
}
