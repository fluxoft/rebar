<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Data\Db\Exceptions\MapperException;
use Fluxoft\Rebar\Data\Db\Filter;
use Fluxoft\Rebar\Data\Db\Join;
use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Data\Db\Property;
use Fluxoft\Rebar\Data\Db\Sort;
use Fluxoft\Rebar\Model;
use PDO;

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
 * @package Fluxoft\Rebar\Data\Db\Mappers
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
	protected string $idProperty = 'Id';
	protected array $propertyDbMap;
	protected array $joins = [];

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

		// Ensure the propertyDbMap is properly initialized
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

		// Ensure idProperty is properly defined in propertyDbMap
		if (!isset($this->propertyDbMap[$this->idProperty])) {
			throw new \InvalidArgumentException(sprintf(
				"ID property '%s' is not defined in the propertyDbMap.",
				$this->idProperty
			));
		}
	}

	public function GetNew(): Model {
		return clone $this->model;
	}

	/**
	 * @param  int $id
	 * @return Model|null
	 * @throws MapperException
	 */
	public function GetOneById(int $id): ?Model {
		$rows = $this->performSelect([
			new Filter($this->propertyDbMap[$this->idProperty]->Column, '=', $id)
		]);
		return !empty($rows) ? $this->getModel($rows[0]) : null;
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
	 * @param Filter[] $filters
	 * @param Sort[] $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return Model[]
	 * @throws MapperException
	 */
	public function GetSet(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array {
		$rows = $this->performSelect($filters, $sort, $page, $pageSize);
		return $this->getModelSet($rows);
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

		if ($model->{$this->idProperty} === null || $model->{$this->idProperty} === 0) {
			// ID is null or 0, indicating a new record
			$this->performCreate($model);
		} else {
			// Existing record, perform update
			$this->performUpdate($model);
		}
	}

	/**
	 * @param array $filters
	 * @return int
	 */
	public function Count(array $filters = []): int {
		return $this->performCount($filters);
	}

	/**
	 * @param Model $model
	 */
	public function Delete(Model &$model): void {
		$this->performDelete([
			$this->idProperty => $model->{$this->idProperty}
		]);
		$model = null; // Nullify the reference to indicate the model is deleted
	}	
	/**
	 * @param int $id
	 * @throws MapperException
	 */
	public function DeleteById(int $id): void {
		$this->performDelete([$this->idProperty => $id]);
	}
	public function DeleteOneWhere(array $filters): void {
		$conditions = [];
		foreach ($filters as $property => $value) {
			if (!isset($this->propertyDbMap[$property])) {
				throw new MapperException(sprintf(
					"Invalid property '%s' in filters.",
					$property
				));
			}
			$conditions[$this->propertyDbMap[$property]->Column] = $value;
		}
	
		$this->performDelete($conditions);
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
	 * Perform a SELECT query on the database.
	 * @param array $filters
	 * @param array $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return array
	 */
	protected function performSelect(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array {
		// Generate SQL and params using the helper
		$selectQuery = $this->getSelectQuery($filters, $sort, $page, $pageSize);
	
		// Execute the query and return results
		return $this->executeQuery($this->reader, $selectQuery['sql'], $selectQuery['params'], true);
	}
	/**
	 * Create a new record in the database.
	 * @param Model $model
	 * @throws Exception
	 */
	protected function performCreate(Model $model): void {
		// Merge properties and modified properties for insertion
		$merged = array_replace_recursive(
			$model->GetProperties(),
			$model->GetModifiedProperties()
		);
	
		// Generate SQL and params using the helper
		$insertQuery = $this->getInsertQuery($merged);
	
		// Execute the query
		$this->executeQuery($this->writer, $insertQuery['sql'], $insertQuery['params'], false);
	
		// Set the model's ID
		$model[$this->idProperty] = $this->writer->lastInsertId();
	}

	/**
	 * Count the number of records in the database that match the filters.
	 * @param array $filters
	 * @return int
	 */
	protected function performCount(array $filters = []): int {
		$countQuery = $this->getCountQuery($filters);
	
		// Prepare and execute the query
		$set = $this->executeQuery($this->reader, $countQuery['sql'], $countQuery['params'], true);
		if (empty($set) || !isset($set[0]['count'])) {
			throw new MapperException('Count query did not return a count.');
		}
		return (int) $set[0]['count'];
	}

	/**
	 * Update an existing record in the database.
	 * @param Model $model
	 * @throws Exception
	 */
	protected function performUpdate(Model $model): void {
		// Get modified properties
		$modified = $model->GetModifiedProperties();
	
		// Prepare the conditions (e.g., WHERE clause)
		$conditions = [
			$this->idProperty => $model->{$this->idProperty}
		];
	
		// Generate SQL and params using the helper
		$updateQuery = $this->getUpdateQuery($modified, $conditions);
	
		// Execute the query
		$this->executeQuery($this->writer, $updateQuery['sql'], $updateQuery['params'], false);
	}
	/**
	 * Delete a record from the database.
	 * @param array $conditions
	 * @throws Exception
	 */
	protected function performDelete(array $conditions): void {
		// Generate SQL and params using the helper
		$deleteQuery = $this->getDeleteQuery($conditions);

		// Execute the query
		$this->executeQuery($this->writer, $deleteQuery['sql'], $deleteQuery['params'], false);
	}

	/**
	 * Execute a query on the database.
	 * @param PDO $dbConnection The database connection to use (use $this->reader or $this->writer)
	 * @param string $sql
	 * @param array $params
	 * @param bool $fetch Whether to fetch the results or not
	 * @return array|null
	 */
	protected function executeQuery(PDO $dbConnection, string $sql, array $params, bool $fetch): ?array {
		try {
			$stmt = $dbConnection->prepare($sql);
			$stmt->execute($params);
			return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : null;
		} catch (\PDOException $e) {
			throw new MapperException('Error executing query: '.$e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param Filter[] $filters Array of Filter objects
	 * @param Sort[] $sort Array of Sort objects
	 * @param int $page
	 * @param int $pageSize
	 * @return array{sql: string, params: array}
	 * @throws MapperException
	 */
	protected function getSelectQuery(
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
	protected function getCountQuery(array $filters = []): array {
		// Start building the COUNT SQL
		$sql = 'SELECT COUNT(*) AS count FROM ' . $this->quoteIdentifier($this->dbTable);
	
		// Include JOIN clauses if they exist
		if (!empty($this->joins)) {
			$sql .= ' ' . $this->buildJoins();
		}
	
		// Apply filters
		$filter = $this->getFilter($filters);
		if (!empty($filter['sql'])) {
			$sql .= $filter['sql'];
		}
	
		return [
			'sql' => $sql,
			'params' => $filter['params']
		];
	}
	protected function getInsertQuery(array $data): array {
		$columns = [];
		$values  = [];
		foreach ($data as $property => $value) {
			if (isset($this->propertyDbMap[$property]) &&
				$this->propertyDbMap[$property]->IsWriteable
			) {
				$columns[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column);
				
				$values[":{$this->propertyDbMap[$property]->Column}"] =
					$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);
			}
		}
	
		$sql = "INSERT INTO " . $this->quoteIdentifier($this->dbTable) . " (" .
			implode(', ', $columns) . ") VALUES (" .
			implode(', ', array_keys($values)) . ")";
	
		return ['sql' => $sql, 'params' => $values];
	}

	protected function getUpdateQuery(array $data, array $conditions): array {
		$set    = [];
		$where  = [];
		$params = [];
		foreach ($data as $property => $value) {
			if (isset($this->propertyDbMap[$property]) &&
				$this->propertyDbMap[$property]->IsWriteable
			) {
				$set[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
					" = :{$this->propertyDbMap[$property]->Column}";

				$params[":{$this->propertyDbMap[$property]->Column}"] =
					$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);
			}
		}
		foreach ($conditions as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				$where[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
					" = :condition_{$this->propertyDbMap[$property]->Column}";

				$params[":condition_{$this->propertyDbMap[$property]->Column}"] = $value;
			}
		}
	
		$sql = "UPDATE " . $this->quoteIdentifier($this->dbTable) .
			" SET " . implode(', ', $set) .
			" WHERE " . implode(' AND ', $where);
	
		return ['sql' => $sql, 'params' => $params];
	}

	protected function getDeleteQuery(array $conditions): array {
		$where  = [];
		$params = [];
		foreach ($conditions as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				$where[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
					" = :{$this->propertyDbMap[$property]->Column}";

				$params[":{$this->propertyDbMap[$property]->Column}"] = $value;
			}
		}
	
		$sql = "DELETE FROM " . $this->quoteIdentifier($this->dbTable) .
			" WHERE " . implode(' AND ', $where);
	
		return ['sql' => $sql, 'params' => $params];
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
	 * @param Sort[] $sorts
	 * @return string
	 */
	protected function applySorting(string $sql, array $sorts): string {
		if (empty($sorts)) {
			return $sql;
		}

		$orderBy = [];
		foreach ($sorts as $sort) {
			if ($sort instanceof Sort) {
				$field = $sort->Property;
				$order = $sort->Direction;
				if (array_key_exists($field, $this->model->GetProperties())) {
					$orderBy[] = $this->quoteIdentifier($field) . " $order";
				}
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
