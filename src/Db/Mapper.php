<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
use Fluxoft\Rebar\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Db\Exceptions\MapperException;

/**
 * Class Mapper
 * @package Fluxoft\Rebar\Db
 */
abstract class Mapper {
	/** @var string */
	protected $modelClass = null;
	/** @var \Fluxoft\Rebar\Db\Model */
	protected $model = null;
	/** @var MapperFactory */
	protected $mapperFactory;
	/** @var Connection */
	protected $reader;
	/** @var Connection */
	protected $writer;
	/** @var string */
	protected $selectSql = null;

	/**
	 * @param MapperFactory $mapperFactory
	 * @param Connection $reader
	 * @param Connection $writer
	 * @throws MapperException
	 */
	public function __construct(
		MapperFactory $mapperFactory,
		Connection $reader,
		Connection $writer = null
	) {
		if (!isset($this->modelClass)) {
			throw new MapperException(sprintf(
				'No modelClass was defined for %s',
				get_class()
			));
		}

		$this->mapperFactory = $mapperFactory;
		$this->reader        = $reader;
		$this->writer        = (isset($writer)) ? $writer : $reader;

		$modelClass = $this->modelClass;

		if (!class_exists($modelClass)) {
			throw new MapperException(sprintf('The model %s could not be found.', $modelClass));
		}
		$this->model = new $modelClass();
	}

	/**
	 * @return Model
	 */
	public function GetNew() {
		return clone $this->model;
	}

	/**
	 * @param $id
	 * @return Model|null
	 */
	public function GetOneById($id) {
		$idProperty = $this->model->GetIDProperty();
		$select     = $this->getSelect([$idProperty => $id], [], 1, 1);
		$results    = $this->reader->fetchAll(
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
	 * @param array $filter
	 * @return null
	 */
	public function GetOneWhere($filter = []) {
		$select  = $this->getSelect($filter, [], 1, 1);
		$results = $this->reader->fetchAll(
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
	 * @param array $filter
	 * @param array $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return array
	 */
	public function GetSetWhere($filter = [], $sort = [], $page = 1, $pageSize = 0) {
		$select  = $this->getSelect($filter, $sort, $page, $pageSize);
		$results = $this->reader->fetchAll(
			$select['sql'],
			$select['params']
		);
		return $this->getModelSet($results);
	}

	/**
	 * @param Model $model
	 */
	public function Save(Model $model) {
		if ($model->GetID() === 0) {
			// ID is set to 0, so this is an INSERT
			$this->Create($model);
		} else {
			// UPDATE for this ID
			$this->Update($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function Delete(Model $model) {
		$idColumn = $model->GetIDColumn();
		$sql      = "DELETE FROM `{$model->GetDbTable()}` WHERE `$idColumn` = :$idColumn";
		$this->writer->executeQuery($sql, ['id' => $model->GetID()], [$model->GetIDType()]);
		$model = null;
	}

	/**
	 * @param $id
	 */
	public function DeleteOneById($id) {
		/** @var Model $model */
		$model = $this->GetOneById($id);
		if ($model !== false) {
			$this->Delete($model);
		}
	}

	/**
	 * @param array $filter
	 */
	public function DeleteOneWhere($filter = []) {
		/** @var Model $model */
		$model = $this->GetOneWhere($filter);
		if ($model !== false) {
			$this->Delete($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws InvalidModelException
	 */
	public function Create(Model $model) {
		if ($model->IsValid()) {
			$idProperty = $model->GetIDProperty();
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
			$model->SetID($insertId);
		} else {
			throw new InvalidModelException('Model failed validation check.');
		}
	}

	/**
	 * @param Model $model
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function Update(Model $model) {
		$idProperty    = $model->GetIDProperty();
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
				$types[]                                    = $propertyDbMap[$idProperty]['type'];

				$sql = "UPDATE `{$model->GetDbTable()}` SET ";
				foreach ($cols as $col) {
					$sql .= "`$col` = :$col,";
				}
				$sql  = substr($sql, 0, -1); // remove trailing comma
				$sql .= " WHERE `{$propertyDbMap[$idProperty]['col']}` = :{$propertyDbMap[$idProperty]['col']}";

				$this->writer->executeQuery($sql, $values, $types);
			}
		}
	}

	/**
	 * @param array $filter Array of property names and values to filter by
	 * @param array $sort Array of property names to sort by in the order they should be applied,
	 *                    e.g. ['Name', 'ID DESC']
	 * @param int $page
	 * @param int $pageSize
	 * @return array Contains 2 elements: 'sql' is the SQL statement, 'params' are the values
	 *               to be passed to the prepared statement
	 */
	private function getSelect($filter = [], $sort = [], $page = 1, $pageSize = 0) {
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
		$params = [];

		// Apply filters, if provided.
		if (!empty($filter)) {
			// If a filter is in the propertyDbMap, it is filtered in the WHERE clause
			$whereFilters = [];
			foreach ($filter as $key => $value) {
				if (isset($propertyDbMap[$key])) {
					$whereFilters[] = "`$dbTable`.`{$propertyDbMap[$key]['col']}` = :$key";
					$params[$key]   = $value;
					unset($filter[$key]);
				}
			}
			if (!empty($whereFilters)) {
				$sql .= ' WHERE '.implode(' AND ', $whereFilters);
			}

			// If a filter is in properties, but wasn't found in propertyDbMap, use a HAVING clause
			$havingFilters = [];
			foreach ($filter as $key => $value) {
				if (isset($properties[$key])) {
					$havingFilters[] = "$key = :$key";
					$params[$key]    = $value;
				}
			}
			if (!empty($havingFilters)) {
				$sql .= ' HAVING '.implode(' AND ', $havingFilters);
			}
		}
		// Apply order, if set
		if (!empty($sort)) {
			$orderBy = [];
			foreach ($sort as $item) {
				list($field) = explode(' ', $item);
				if (array_key_exists($field, $properties)) {
					$orderBy[] = $item;
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

	protected function getModelSet($rowSet) {
		$models = [];
		foreach ($rowSet as $row) {
			$models[] = new $this->modelClass($row);
		}
		return $models;
	}
	protected function getModel($row) {
		return new $this->modelClass($row);
	}
}
