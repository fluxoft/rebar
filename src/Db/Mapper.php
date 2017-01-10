<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
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
		$idProperty    = $this->model->GetIDProperty();
		$propertyDbMap = $this->model->GetPropertyDbMap();
		$sql           = "SELECT * FROM `{$this->model->GetDbTable()}` WHERE `{$propertyDbMap[$idProperty]['col']}` = :id";
		$values        = ['id' => $id];
		$types         = [$propertyDbMap[$idProperty]['type']];
		$results       = $this->reader->fetchAll(
			$sql,
			$values,
			$types
		);
		if (!empty($results)) {
			return new $this->modelClass($results[0]);
		} else {
			return null;
		}
	}

	/**
	 * @param $where
	 * @param array $params
	 * @return null
	 */
	public function GetOneWhere($where, $params = []) {
		$propertyDbMap = $this->model->GetPropertyDbMap();
		$sql           = "SELECT * FROM `{$this->model->GetDbTable()}`";
		if (!empty($where)) {
			$sql .= $this->translateWhere($where, $propertyDbMap);
		}
		$sql   .= ' LIMIT 1';
		$values = [];
		$types  = [];
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$values[$key] = $value['value'];
				if (isset($value['type'])) {
					$types[] = $value['type'];
				}
			} else {
				$values[$key] = $value;
			}
		}
		$results = $this->reader->fetchAll(
			$sql,
			$values,
			$types
		);
		if (!empty($results)) {
			return new $this->modelClass($results[0]);
		} else {
			return null;
		}
	}

	/**
	 * @param string $where
	 * @param array $params
	 * @param int $page
	 * @param int $pageSize
	 * @return array
	 */
	public function GetSetWhere($where = '', $params = [], $page = 1, $pageSize = 0) {
		$propertyDbMap = $this->model->GetPropertyDbMap();
		$sql           = "SELECT * FROM `{$this->model->GetDbTable()}`";
		if (!empty($where)) {
			$sql .= $this->translateWhere($where, $propertyDbMap);
		}
		if ($pageSize > 0) {
			$sql .= " LIMIT $pageSize OFFSET " . ($pageSize * ($page - 1));
		}
		$values = [];
		$types  = [];
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$values[$key] = $value['value'];
				if (isset($value['type'])) {
					$types[] = $value['type'];
				}
			} else {
				$values[$key] = $value;
			}
		}
		$set     = [];
		$results = $this->reader->fetchAll(
			$sql,
			$values,
			$types
		);
		foreach ($results as $result) {
			$set[] = new $this->modelClass($result);
		}
		return $set;
	}

	/**
	 * @param Model $model
	 */
	public function Save(Model $model) {
		if ($model->GetID() === 0) {
			// ID is set to 0, so this is an INSERT
			$this->create($model);
		} else {
			// UPDATE for this ID
			$this->update($model);
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
	 * @param $where
	 * @param $params
	 */
	public function DeleteOneWhere($where, $params) {
		/** @var Model $model */
		$model = $this->GetOneWhere($where, $params);
		if ($model !== false) {
			$this->Delete($model);
		}
	}

	/**
	 * @param Model $model
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function create(Model $model) {
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
	}

	/**
	 * @param Model $model
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function update(Model $model) {
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
	 * @param $where
	 * @param $properties
	 * @return string
	 */
	private function translateWhere($where, $properties) {
		// @todo: clean up this hacky mess - the methods here should really just accept an array of filter values
		$returnWhere  = ' WHERE ';
		$returnWhere .= preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($properties) {
			if (isset($properties[$matches[1]])) {
				return '`' . $properties[$matches[1]]['col'] . '`';
			} else {
				return '{' . $matches[1] . '}';
			}
		}, $where);

		if (preg_match('/\{(\w+)\}/', $returnWhere)) {
			$returnWhere = '';
		}

		return $returnWhere;
	}
}
