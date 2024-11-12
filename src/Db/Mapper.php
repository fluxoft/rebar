<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Fluxoft\Rebar\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Db\Exceptions\MapperException;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Mapper
 * @package Fluxoft\Rebar\Db
 * @deprecated Use Fluxoft\Rebar\Mappers instead
 */
abstract class Mapper {
	/** @var MapperFactory */
	protected MapperFactory $mapperFactory;
	/** @var Model */
	protected Model $model;
	/** @var Connection */
	protected Connection $reader;
	/** @var Connection */
	protected Connection $writer;
	/** @var string|null */
	protected ?string $selectSql = null;
	/** @var string|null */
	protected ?string $countSql = null;

	/**
	 * @param MapperFactory $mapperFactory
	 * @param Model $model
	 * @param Connection $reader
	 * @param Connection|null $writer
	 */
	public function __construct(
		MapperFactory $mapperFactory,
		Model         $model,
		Connection    $reader,
		Connection    $writer = null
	) {
		$this->mapperFactory = $mapperFactory;
		$this->model         = $model;
		$this->reader        = $reader;
		$this->writer        = (isset($writer)) ? $writer : $reader;
	}

	/**
	 * @return Model|null
	 */
	public function GetNew(): ?Model {
		return clone $this->model;
	}

	/**
	 * @param $id
	 * @return Model|null
	 * @throws Exception
	 */
	public function GetOneById($id): ?Model {
		$idProperty = $this->model->GetIdProperty();
		$select     = $this->getSelect(
			[new Filter($idProperty, '=', $id)],
			[],
			1,
			1);
		$results    = $this->reader->fetchAllAssociative(
			$select['sql'],
			$select['params']
		);
		if (!empty($results)) {
			return $this->getModel($results[0]);
		} else {
			return null;
		}
	}

	/**
	 * @param Filter[] $filters
	 * @return Model|null
	 * @throws Exception
	 */
	public function GetOneWhere(array $filters = []): ?Model {
		$select  = $this->getSelect($filters, [], 1, 1);
		$results = $this->reader->fetchAllAssociative(
			$select['sql'],
			$select['params']
		);
		if (!empty($results)) {
			return $this->getModel($results[0]);
		} else {
			return null;
		}
	}

	/**
	 * @param Filter[] $filters
	 * @param array $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return Model[]
	 * @throws Exception
	 */
	public function GetSetWhere(
		array $filters = [],
		array $sort = [],
		int   $page = 1,
		int   $pageSize = 0
	): array {
		$select  = $this->getSelect($filters, $sort, $page, $pageSize);
		$results = $this->reader->fetchAllAssociative(
			$select['sql'],
			$select['params']
		);
		return $this->getModelSet($results);
	}

	/**
	 * @param Filter[] $filters
	 * @return int
	 * @throws Exception
	 */
	public function CountWhere(array $filters = []): int {
		$count  = $this->countSelect($filters);
		$stmt   = $this->reader->prepare($count['sql']);
		$result = $stmt->executeQuery($count['params']);
		$total  = $result->fetchOne();
		return (int) $total;
	}

	/**
	 * @param Model $model
	 * @throws InvalidModelException
	 * @throws Exception
	 */
	public function Save(Model $model) {
		if ($model->GetId() === 0) {
			// ID is set to 0, so this is an INSERT
			$this->Create($model);
		} else {
			// UPDATE for this ID
			$this->Update($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws Exception
	 */
	public function Delete(Model &$model) {
		$idColumn = $model->GetIdColumn();
		$sql      = "DELETE FROM `{$model->GetDbTable()}` WHERE `$idColumn` = :$idColumn";
		$this->writer->executeQuery($sql, ['id' => $model->GetId()], [$model->GetIdType()]);
		$model = null;
	}

	/**
	 * @param $id
	 * @throws Exception
	 */
	public function DeleteOneById($id) {
		/** @var Model $model */
		$model = $this->GetOneById($id);
		if (isset($model)) {
			$this->Delete($model);
		}
	}

	/**
	 * @param array $filters
	 * @throws Exception
	 */
	public function DeleteOneWhere(array $filters = []) {
		/** @var Model $model */
		$model = $this->GetOneWhere($filters);
		if (isset($model)) {
			$this->Delete($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws InvalidModelException|Exception
	 */
	public function Create(Model $model) {
		if ($model->IsValid()) {
			$idProperty = $model->GetIdProperty();
			// merged array containing original plus modified properties
			$merged        = array_replace_recursive(
				$model->GetProperties(),
				$model->GetModifiedProperties()
			);
			$propertyDbMap = $model->GetPropertyDbMap();

			$cols   = [];
			$types  = [];
			$values = [];
			foreach ($merged as $property => $value) {
				if ($property !== $idProperty && !is_null($value) && isset($propertyDbMap[$property])) {
					$cols[]  = $propertyDbMap[$property]['col'];
					$types[] = $propertyDbMap[$property]['type'];

					$values[$propertyDbMap[$property]['col']] = $value;
				}
			}
			$sql = "INSERT INTO `{$model->GetDbTable()}` (`" .
				implode('`,`', $cols) .
				"`) VALUES (:" . implode(',:', $cols) . ")";
			$this->writer->executeQuery($sql, $values, $types);
			$insertId = $this->writer->lastInsertId();
			$model->SetId($insertId);
		} else {
			throw new InvalidModelException('Model failed validation check.');
		}
	}

	/**
	 * @param Model $model
	 * @throws Exception
	 * @throws InvalidModelException
	 */
	public function Update(Model $model) {
		if ($model->IsValid()) {
			$idProperty    = $model->GetIdProperty();
			$properties    = $model->GetProperties();
			$modified      = $model->GetModifiedProperties();
			$propertyDbMap = $model->GetPropertyDbMap();
			if (!empty($modified)) {
				$cols   = [];
				$types  = [];
				$values = [];
				foreach ($modified as $property => $value) {
					if (isset($propertyDbMap[$property])) {
						$cols[]  = $propertyDbMap[$property]['col'];
						$types[] = $propertyDbMap[$property]['type'];

						$values[$propertyDbMap[$property]['col']] = $value;
					}
				}
				if (!empty($cols)) {
					$values[$propertyDbMap[$idProperty]['col']] = $properties[$idProperty];

					$types[] = $propertyDbMap[$idProperty]['type'];

					$sql = "UPDATE `{$model->GetDbTable()}` SET ";
					foreach ($cols as $col) {
						$sql .= "`$col` = :$col,";
					}
					$sql  = substr($sql, 0, -1); // remove trailing comma
					$sql .= " WHERE `{$propertyDbMap[$idProperty]['col']}` = :{$propertyDbMap[$idProperty]['col']}";

					$this->writer->executeQuery($sql, $values, $types);
				}
			}
		} else {
			throw new InvalidModelException('Model failed validation check.');
		}
	}

	/**
	 * @param Filter[] $filters Array of Filter objects
	 * @param array $sort Array of property names to sort by in the order they should be applied,
	 *                    e.g. ['Name', 'ID DESC']
	 * @param int $page
	 * @param int $pageSize
	 * @return array Contains 2 elements: 'sql' is the SQL statement, 'params' are the values
	 *               to be passed to the prepared statement
	 * @throws MapperException
	 */
	#[ArrayShape(['sql' => "null|string", 'params' => "array|\array|mixed|mixed"])]
	protected function getSelect(
		array $filters = [],
		array $sort = [],
		int   $page = 1,
		int   $pageSize = 0
	): array {
		$dbTable       = $this->model->GetDbTable();
		$properties    = $this->model->GetProperties();
		$propertyDbMap = $this->model->GetPropertyDbMap();

		if (!isset($this->selectSql)) {
			$fields = [];
			foreach ($propertyDbMap as $name => $dbMap) {
				$fields[] = "`$dbTable`.`{$dbMap['col']}` $name";
			}
			$this->selectSql = 'SELECT '.implode(', ', $fields).' FROM `'.$dbTable.'`';
		}

		$sql    = $this->selectSql;
		$filter = $this->getFilter($filters);
		$params = [];
		if (strlen($filter['sql']) > 0) {
			$sql   .= $filter['sql'];
			$params = $filter['params'];
		}
		// Apply order, if set
		if (!empty($sort)) {
			$orderBy = [];
			foreach ($sort as $item) {
				$itemBits = explode(' ', $item);
				$field    = $itemBits[0];
				if (isset($itemBits[1]) && strtolower($itemBits[1]) === 'desc') {
					$order = 'DESC';
				} else {
					$order = 'ASC';
				}
				if (array_key_exists($field, $properties)) {
					$orderBy[] = "$field $order";
				}
			}
			if (!empty($orderBy)) {
				$sql .= ' ORDER BY ' . implode(', ', $orderBy);
			}
		}
		// Apply limit, if limited
		if ($pageSize > 0) {
			$sql .= " LIMIT $pageSize OFFSET " . ($pageSize * ($page - 1));
		}
		return [
			'sql' => $sql,
			'params' => $params
		];
	}

	/**
	 * @param Filter[] $filters Array of property names and values to filter by
	 * @return array Contains 2 elements: 'sql' is the SQL statement, 'params' are the values
	 *               to be passed to the prepared statement
	 */
	#[ArrayShape(['sql' => "null|string", 'params' => "array|mixed"])]
	protected function countSelect(array $filters = []): array {
		$dbTable       = $this->model->GetDbTable();
		$propertyDbMap = $this->model->GetPropertyDbMap();

		if (!isset($this->countSql)) {
			$idField        = $propertyDbMap[$this->model->GetIdProperty()]['col'];
			$this->countSql = 'SELECT COUNT('.$idField.') FROM `'.$dbTable.'`';
		}

		$sql    = $this->countSql;
		$filter = $this->getFilter($filters);
		$params = [];
		if (strlen($filter['sql']) > 0) {
			$sql   .= $filter['sql'];
			$params = $filter['params'];
		}

		return [
			'sql' => $sql,
			'params' => $params
		];
	}

	/**
	 * @throws MapperException
	 */
	#[ArrayShape(['sql' => "string", 'params' => "array|mixed"])]
	protected function getFilter($filters = []): array {
		$dbTable       = $this->model->GetDbTable();
		$properties    = $this->model->GetProperties();
		$propertyDbMap = $this->model->GetPropertyDbMap();
		$params        = [];
		$whereFilters  = [];
		$havingFilters = [];
		$filterString  = '';

		// Apply filters, if provided.
		if (!empty($filters)) {
			// If a filter is in the propertyDbMap, it is filtered in the WHERE clause
			foreach ($filters as $filter) {
				switch (strtoupper($filter->Operator)) {
					case 'IN':
						$placeHolders = [];
						foreach ($filter->Value as $phKey => $value) {
							$placeHolder          = $filter->Property.'_'.$phKey;
							$placeHolders[]       = ':'.$placeHolder;
							$params[$placeHolder] = $value;
						}
						$filterSql = 'IN ('.
							implode(',', $placeHolders).')';
						break;
					case 'BETWEEN':
						$filterSql = sprintf(
							'BETWEEN :%s AND :%s',
							$filter->Property.'_low',
							$filter->Property.'_high'
						);

						$params[$filter->Property.'_low']  = $filter->Value[0];
						$params[$filter->Property.'_high'] = $filter->Value[1];
						break;
					default:
						$filterSql                 =
							"$filter->Operator :{$filter->Property}";
						$params[$filter->Property] = $filter->Value;
				}

				// If this is in propertyDbMap, it should be part of the WHERE clause
				if (isset($propertyDbMap[$filter->Property])) {
					$whereFilters[] = "`$dbTable`.`{$propertyDbMap[$filter->Property]['col']}` $filterSql";
				}
				// If not in propertyDbMap but in properties, put it in HAVING clause
				elseif (isset($properties[$filter->Property])) {
					$havingFilters[]           = "$filter->Property $filterSql";
					$params[$filter->Property] = $filter->Value;
				} else {
					// If a filter was set that is neither in propertyDbMap nor in properties, it's an error
					throw new MapperException(sprintf(
						'Trying to filter on a non-property: %s',
						$filter->Property
					));
				}
			}
		}
		if (!empty($whereFilters)) {
			$filterString .= ' WHERE '.implode(' AND ', $whereFilters);
		}
		if (!empty($havingFilters)) {
			$filterString .= ' HAVING '.implode(' AND ', $havingFilters);
		}
		return [
			'sql' => $filterString,
			'params' => $params
		];
		/*$filterClauses['params'] = $params;
		return $filterClauses;*/
	}

	protected function getModelSet($rowSet): array {
		$models = [];
		foreach ($rowSet as $row) {
			$models[] = $this->getModel($row);
		}
		return $models;
	}
	protected function getModel($row): ?Model {
		$model = $this->GetNew();
		$model->InitializeProperties($row);
		return $model;
	}
}
