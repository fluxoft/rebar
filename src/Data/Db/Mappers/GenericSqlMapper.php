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
abstract class GenericSqlMapper implements MapperInterface {
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

		$this->initializePropertyDbMap();
		$this->validateJoins();
		$this->validateIdProperty();
	}

	// Constructor initialization tasks
	protected function initializePropertyDbMap(): void {
		foreach ($this->propertyDbMap as $property => &$dbMap) {
			if ($dbMap instanceof Property) {
				continue;
			}
			if (is_string($dbMap)) {
				$this->propertyDbMap[$property] = new Property($dbMap, 'string');
			} elseif (is_array($dbMap)) {
				$column = $dbMap['column'] ?? $property;
				$type   = $dbMap['type'] ?? 'string';

				$this->propertyDbMap[$property] = new Property($column, $type);
			} else {
				throw new \InvalidArgumentException("Invalid property definition for $property.");
			}
		}
	}
	protected function validateJoins(): void {
		foreach ($this->joins as $join) {
			if (!$join instanceof Join) {
				throw new MapperException('Invalid join definition. Expected instance of Join.');
			}
		}
	}
	protected function validateIdProperty(): void {
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
	 * @param  mixed $id
	 * @return Model|null
	 * @throws MapperException
	 */
	public function GetOneById(mixed $id): ?Model {
		return $this->GetOne([new Filter($this->idProperty, '=', $id)]);
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

	public function Count(array $filters = []): int {
		return $this->performCount($filters);
	}

	public function Delete(Model &$model): void {
		if (empty($model->{$this->idProperty})) {
			throw new \InvalidArgumentException('Cannot delete a model without a valid ID.');
		}

		$this->performDelete([
			$this->idProperty => $model->{$this->idProperty}
		]);
		$model = null; // Nullify the reference to indicate the model is deleted
	}

	public function DeleteById(mixed $id): void {
		if (empty($id)) {
			throw new \InvalidArgumentException('Cannot delete a record without a valid ID.');
		}

		$this->performDelete([$this->idProperty => $id]);
	}

	public function DeleteOneWhere(array $filters): void {
		$conditions = [];
		foreach ($filters as $property => $value) {
			// Instead of throwing an error here, directly map the filters to conditions.
			if (!isset($this->propertyDbMap[$property])) {
				throw new MapperException(sprintf(
					"Invalid property '%s' in filters.",
					$property
				));
			}
			$conditions[$property] = $value;
		}

		// Pass the mapped conditions directly to performDelete.
		$this->performDelete($conditions);
	}

	/**
	 * Format an identifier for use in SQL
	 * This method can be overridden in the extending class if the database server requires
	 * @param string $element
	 * @return string
	 */
	protected function quoteElement(string $element): string {
		return $element;
	}
	protected function quoteIdentifier(string $identifier): string {
		if (strpos($identifier, '.') !== false) {
			$parts = explode('.', $identifier);
			return $this->quoteElement($parts[0]) . '.' . $this->quoteElement($parts[1]);
		}
		return $this->quoteElement($identifier);
	}

	protected function formatValueForInsert(string $type, mixed $value): mixed {
		// Handle DateTime objects
		if ($value instanceof \DateTime) {
			switch ($type) {
				case 'datetime': // @codeCoverageIgnore
					return $value->format('Y-m-d H:i:s');
				case 'date': // @codeCoverageIgnore
					return $value->format('Y-m-d');
				case 'time': // @codeCoverageIgnore
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
	 */
	protected function performCreate(Model $model): void {
		$properties = array_intersect_key(
			$model->GetProperties(),
			$this->propertyDbMap // Only include mapped properties
		);

		// Do not include the Id property in the INSERT query
		unset($properties[$this->idProperty]);

		$merged = array_replace_recursive($properties, $model->GetModifiedProperties());

		$insertQuery = $this->getInsertQuery($merged);
		$this->executeQuery($this->writer, $insertQuery['sql'], $insertQuery['params'], false);

		$model->{$this->idProperty} = (int) $this->writer->lastInsertId();
	}

	/**
	 * Update an existing record in the database.
	 * @param Model $model
	 */
	protected function performUpdate(Model $model): void {
		$properties = array_intersect_key(
			$model->GetModifiedProperties(),
			$this->propertyDbMap // Only include mapped properties
		);

		$conditions = [
			$this->idProperty => $model->{$this->idProperty}
		];

		$updateQuery = $this->getUpdateQuery($properties, $conditions);
		$this->executeQuery($this->writer, $updateQuery['sql'], $updateQuery['params'], false);
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
	 * Delete a record from the database.
	 * @param array $conditions
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
	 * @return ?array
	 */
	protected function executeQuery(PDO $dbConnection, string $sql, array $params, bool $fetch = false): ?array {
		try {
			$stmt = $dbConnection->prepare($sql);
			$stmt->execute($params);
			return $fetch ? $stmt->fetchAll(PDO::FETCH_ASSOC) : null;
		} catch (\PDOException $e) {
			$code = is_numeric($e->getCode()) ? (int) $e->getCode() : 0;
			throw new MapperException('Error executing query: '.$e->getMessage(), $code, $e);
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
				$column = $propertyObject->Column;

				if ($propertyObject->IsAggregate ||
					$propertyObject->IsSubquery
				) { // If the property is an aggregate or subquery, add it without prefix
					$fields[] = "$column AS {$this->quoteIdentifier($property)}";
				} elseif (strpos($column, '.') !== false) { // Check if the column already includes a table, e.g. "groups.name"
					$fields[] = $this->quoteIdentifier($column) . " AS {$this->quoteIdentifier($property)}";
				} else { // Otherwise, prefix the column with the table name
					$fields[] = $this->quoteIdentifier($this->dbTable) . '.' . $this->quoteIdentifier($column) .
						" AS {$this->quoteIdentifier($property)}";
				}
			}
			$this->selectSql = 'SELECT '.implode(', ', $fields).' FROM '.$this->quoteIdentifier($this->dbTable);

			// Add JOIN clauses
			$this->selectSql .= ' ' . $this->buildJoins();
		}

		$sql    = $this->selectSql;
		$filter = $this->getFilter($filters);
		$params = [];

		// Add WHERE clause
		if (!empty($filter['where'])) {
			$sql = rtrim($sql) . $filter['where'];
		}

		// Apply grouping
		$sql = $this->applyGrouping($sql);

		// Add HAVING clause
		if (!empty($filter['having'])) {
			$sql = rtrim($sql) . $filter['having'];
		}

		// Apply sorting
		$sql = $this->applySorting($sql, $sort);

		// Apply pagination
		$sql = $this->applyPagination($sql, $page, $pageSize);

		// Merge parameters
		$params = array_merge($params, $filter['params']);

		return [
			'sql' => trim($sql),
			'params' => $params
		];
	}
	protected function getCountQuery(array $filters = []): array {
		// Start building the COUNT SQL
		$sql = 'SELECT COUNT(*) AS count FROM ' . $this->quoteIdentifier($this->dbTable);

		// Include JOIN clauses if they exist
		$sql .= ' ' . $this->buildJoins();

		// Apply filters
		$filter = $this->getFilter($filters);
		if (!empty($filter['where'])) {
			$sql = rtrim($sql) . $filter['where'];
		}

		return [
			'sql' => trim($sql),
			'params' => $filter['params']
		];
	}
	protected function getInsertQuery(array $data): array {
		$columns = [];
		$values  = [];

		// Track whether any valid data exists
		$hasValidData = false;

		foreach ($data as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				// Check if the property is writeable
				if ($this->propertyDbMap[$property]->IsWriteable) {
					$columns[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column);

					$values[":{$this->propertyDbMap[$property]->Column}"] =
						$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);
					$hasValidData                                         = true;
				}
			} else {
				// Throw exception for unmapped properties
				throw new \Fluxoft\Rebar\Data\Db\Exceptions\MapperException(sprintf(
					"Trying to insert a non-mapped property: %s",
					$property
				));
			}
		}

		// If no valid data was found, throw an exception
		if (!$hasValidData) {
			throw new \InvalidArgumentException('No valid data provided for insert.');
		}

		$sql = "INSERT INTO " . $this->quoteIdentifier($this->dbTable) . " (" .
			implode(', ', $columns) . ") VALUES (" .
			implode(', ', array_keys($values)) . ")";

		return ['sql' => $sql, 'params' => $values];
	}

	protected function getUpdateQuery(array $data, array $conditions): array {
		$set          = [];
		$where        = [];
		$params       = [];
		$hasValidData = false;

		// Process data for the SET clause
		foreach ($data as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				if ($this->propertyDbMap[$property]->IsWriteable) {
					$set[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
						" = :{$this->propertyDbMap[$property]->Column}";

					$params[":{$this->propertyDbMap[$property]->Column}"] =
						$this->formatValueForInsert($this->propertyDbMap[$property]->Type, $value);

					$hasValidData = true;
				}
			} else {
				// Throw exception for unmapped properties
				throw new \Fluxoft\Rebar\Data\Db\Exceptions\MapperException(sprintf(
					"Trying to update a non-mapped property: %s",
					$property
				));
			}
		}

		// Check if there is at least one valid data field to update
		if (!$hasValidData) {
			throw new \InvalidArgumentException('No valid data provided for update.');
		}

		// Process conditions for the WHERE clause
		foreach ($conditions as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				$where[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
					" = :condition_{$this->propertyDbMap[$property]->Column}";

				$params[":condition_{$this->propertyDbMap[$property]->Column}"] = $value;
			} else {
				// Throw exception for unmapped condition properties
				throw new \Fluxoft\Rebar\Data\Db\Exceptions\MapperException(sprintf(
					"Trying to filter on a non-mapped property: %s",
					$property
				));
			}
		}

		// Check if there is at least one valid condition
		if (empty($where)) {
			throw new \InvalidArgumentException('No conditions provided for update.');
		}

		// Build the SQL query
		$sql = "UPDATE " . $this->quoteIdentifier($this->dbTable) .
			" SET " . implode(', ', $set) .
			" WHERE " . implode(' AND ', $where);

		return ['sql' => $sql, 'params' => $params];
	}

	protected function getDeleteQuery(array $conditions): array {
		$where  = [];
		$params = [];

		// Ensure conditions are not empty
		if (empty($conditions)) {
			throw new \InvalidArgumentException('No conditions provided for delete.');
		}

		// Process conditions for the WHERE clause
		foreach ($conditions as $property => $value) {
			if (isset($this->propertyDbMap[$property])) {
				$where[] = $this->quoteIdentifier($this->propertyDbMap[$property]->Column) .
					" = :{$this->propertyDbMap[$property]->Column}";

				$params[":{$this->propertyDbMap[$property]->Column}"] = $value;
			} else {
				// Throw exception for unmapped condition properties
				throw new \Fluxoft\Rebar\Data\Db\Exceptions\MapperException(sprintf(
					"Trying to filter on a non-mapped property: %s",
					$property
				));
			}
		}

		$sql = "DELETE FROM " . $this->quoteIdentifier($this->dbTable) .
			" WHERE " . implode(' AND ', $where);

		return ['sql' => $sql, 'params' => $params];
	}

	/**
	 * Determine if there are aggregates in the SELECT clause.
	 */
	protected function hasAggregatesInSelect(): bool {
		foreach ($this->propertyDbMap as $propertyObject) {
			if ($propertyObject->IsAggregate) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add a GROUP BY clause to the SQL statement if there are aggregates in the select.
	 * @param string $sql
	 */
	protected function applyGrouping(string $sql): string {
		if ($this->hasAggregatesInSelect()) {
			$groupByColumns = [];
			$seenColumns    = [];

			// Process propertyDbMap columns (including JOINed table columns)
			foreach ($this->propertyDbMap as $property) {
				if (!$property->IsAggregate && !$property->IsSubquery) {
					$column = $this->isFullyQualified($property->Column)
						? $this->quoteIdentifier($property->Column)
						: $this->quoteIdentifier($this->dbTable) . '.' . $this->quoteIdentifier($property->Column);

					// Avoid duplicates
					if (!in_array($column, $seenColumns, true)) {
						$groupByColumns[] = $column;
						$seenColumns[]    = $column;
					}
				}
			}

			// Append GROUP BY clause if columns exist
			if (!empty($groupByColumns)) {
				$sql .= ' GROUP BY ' . implode(', ', $groupByColumns);
			}
		}

		return $sql;
	}

	protected function isFullyQualified(string $column): bool {
		// Detect if the column already specifies a table (e.g., "groups.name")
		return strpos($column, '.') !== false;
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
				if (isset($this->propertyDbMap[$field])) {
					$orderBy[] = $this->quoteIdentifier($this->dbTable) . '.' .
						$this->quoteIdentifier($this->propertyDbMap[$field]->Column) . " $order";
				}
			}
		}

		if (!empty($orderBy)) {
			$sql = rtrim($sql) . ' ORDER BY ' . implode(', ', $orderBy);
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
			$sql    = rtrim($sql) . " LIMIT $pageSize OFFSET $offset";
		}
		return $sql;
	}

	/**
	 * Build the JOIN clauses for the SQL statement.
	 * @return string
	 */
	protected function buildJoins(): string {
		$joinClauses = [];
		/** @var Join $join */
		foreach ($this->joins as $join) {
			$alias         = $join->Alias ? " AS {$this->quoteIdentifier($join->Alias)}" : '';
			$joinClauses[] = "{$join->Type} JOIN {$this->quoteIdentifier($join->Table)} ON {$join->On}{$alias}";
		}
		return implode(' ', $joinClauses);
	}

	/**
	 * @param Filter[] $filters
	 * @return array{sql: string, params: array} Returns an array with the SQL for
	 * the combined WHERE and HAVING clauses and the parameters.
	 * @throws MapperException
	 */
	protected function getFilter(array $filters = []): array {
		$params        = [];
		$whereFilters  = [];
		$havingFilters = [];

		foreach ($filters as $filter) {
			$filterResult = $this->buildFilterSql($filter);

			// Decide whether this filter belongs in WHERE or HAVING
			if (isset($this->propertyDbMap[$filter->Property])) {
				if ($this->propertyDbMap[$filter->Property]->IsAggregate || $this->propertyDbMap[$filter->Property]->IsSubquery) {
					// Use alias in HAVING
					$havingFilters[] = $this->quoteIdentifier($filter->Property) . ' ' . $filterResult['filter'];
				} else {
					// Use column name in WHERE
					$whereFilters[] = $filterResult['column'] . ' ' . $filterResult['filter'];
				}
			} elseif (isset($this->model->GetProperties()[$filter->Property])) {
				// Fallback to alias for model properties
				// I do not know how this might be used, but I am leaving it in place for now.
				// The 99% coverage on this file will eventually lead me to revisit this and decide if it is needed.
				// Note for future me:
				// 'Age' => new Property('TIMESTAMPDIFF(YEAR, Birthday, CURDATE())', 'integer')
				// Think about how to implement this in the future (although this would probably end up being its own
				// custom code path, not a fallback like this).
				$havingFilters[] = $this->quoteIdentifier($filter->Property) . ' ' . $filterResult['filter'];
			} else {
				throw new MapperException(sprintf(
					'Trying to filter on a non-property: %s',
					$filter->Property
				));
			}

			// Merge parameters
			$params = array_merge($params, $filterResult['params']);
		}

		// Combine WHERE and HAVING clauses
		$whereClause  = $this->combineWhereFilters($whereFilters);
		$havingClause = $this->combineHavingFilters($havingFilters);

		return [
			'where'  => $whereClause,
			'having' => $havingClause,
			'params' => $params
		];
	}

	protected function buildFilterSql(Filter $filter): array {
		$params = [];
		$column = isset($this->propertyDbMap[$filter->Property])
			? $this->quoteIdentifier($this->dbTable . '.' . $this->propertyDbMap[$filter->Property]->Column)
			: $this->quoteIdentifier($filter->Property);

		switch (strtoupper($filter->Operator)) {
			case 'IN': // @codeCoverageIgnore
				$placeholders = [];
				foreach ($filter->Value as $phKey => $value) {
					$placeholder          = ":{$filter->Property}_$phKey";
					$placeholders[]       = $placeholder;
					$params[$placeholder] = $value;
				}
				$filterSql = "IN (".implode(', ', $placeholders).")";
				break;

			case 'BETWEEN': // @codeCoverageIgnore
				$filterSql = "BETWEEN :{$filter->Property}_min AND :{$filter->Property}_max";

				$params[":{$filter->Property}_min"] = $filter->Value[0];
				$params[":{$filter->Property}_max"] = $filter->Value[1];
				break;

			default:
				$filterSql = "{$filter->Operator} :{$filter->Property}";

				$params[":{$filter->Property}"] = $filter->Value;
				break;
		}

		return [
			'column' => $column,
			'filter' => $filterSql,
			'params' => $params
		];
	}


	protected function combineWhereFilters(array $whereFilters): string {
		if (!empty($whereFilters)) {
			return ' WHERE ' . implode(' AND ', $whereFilters);
		}
		return '';
	}

	protected function combineHavingFilters(array $havingFilters): string {
		if (!empty($havingFilters)) {
			return ' HAVING ' . implode(' AND ', $havingFilters);
		}
		return '';
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
