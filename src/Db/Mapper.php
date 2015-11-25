<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
use Fluxoft\Rebar\Db\Exceptions\MapperException;

abstract class Mapper {
	/**
	 * @var string $modelClass
	 */
	protected $modelClass = null;

	/** @var \Fluxoft\Rebar\Db\Model */
	protected $model = null;
	/** @var MapperFactory */
	protected $mapperFactory;
	/** @var Connection */
	protected $reader;
	/** @var Connection */
	protected $writer;

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

	public function GetNew() {
		return clone $this->model;
	}

	public function GetOneById($id) {
		$idProperty = $this->model->GetIDProperty();
		$properties = $this->model->GetProperties();
		$sql        = "SELECT * FROM `{$this->model->GetDbTable()}` WHERE `{$properties[$idProperty]['col']}` = :id";
		$values     = ['id' => $id];
		$types      = [$properties[$idProperty]['type']];
		$results    = $this->reader->fetchAll(
			$sql,
			$values,
			$types
		);
		if (!empty($results)) {
			return new $this->modelClass($results[0]);
		} else {
			return false;
		}
	}

	public function GetOneWhere($where, $params = []) {
		$properties = $this->model->GetProperties();
		$sql        = "SELECT * FROM `{$this->model->GetDbTable()}`";
		if (!empty($where)) {
			$sql .= $this->translateWhere($where, $properties);
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
			return false;
		}
	}

	public function GetSetWhere($where = '', $params = [], $page = 1, $pageSize = 0) {
		$properties = $this->model->GetProperties();
		$sql        = "SELECT * FROM `{$this->model->GetDbTable()}`";
		if (!empty($where)) {
			$sql .= $this->translateWhere($where, $properties);
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

	public function Save(Model $model) {
		if ($model->GetID() === 0) {
			// ID is set to 0, so this is an INSERT
			$this->create($model);
		} else {
			// UPDATE for this ID
			$this->update($model);
		}
	}

	public function Delete(Model $model) {
		$idColumn = $model->GetIDColumn();
		$sql = "DELETE FROM `{$model->GetDbTable()}` WHERE `$idColumn` = :$idColumn";
		$this->writer->executeQuery($sql, ['id' => $model->GetID()], [$model->GetIDType()]);
		$model = null;
	}

	public function DeleteOneById($id) {
		/** @var Model $model */
		$model = $this->GetOneById($id);
		if ($model !== false) {
			$this->Delete($model);
		}
	}

	public function DeleteOneWhere($where, $params) {
		/** @var Model $model */
		$model = $this->GetOneWhere($where, $params);
		if ($model !== false) {
			$this->Delete($model);
		}
	}

	private function create(Model $model) {
		$idProperty = $model->GetIDProperty();
		// merged array containing original plus modified properties
		$merged = array_replace_recursive($model->GetProperties(), $model->GetModifiedProperties());
		foreach ($merged as $key => $property) {
			if (!isset($property['value'])) {
				unset($merged[$key]);
			}
		}
		$cols   = [];
		$types  = [];
		$values = [];
		foreach ($merged as $property => $dbMap) {
			if ($property !== $idProperty) {
				$cols[]  = $dbMap['col'];
				$types[] = $dbMap['type'];

				$values[$dbMap['col']] = $dbMap['value'];
			}
		}
		$sql = "INSERT INTO `{$model->GetDbTable()}` (`".
			implode('`,`', $cols) .
			"`) VALUES (:" . implode(',:', $cols) . ")";
		$this->writer->executeQuery($sql, $values, $types);
		$insertId = $this->writer->lastInsertId();
		$model->SetID($insertId);
	}

	private function update(Model $model) {
		$idProperty = $model->GetIDProperty();
		$properties = $model->GetProperties();
		$modified   = $model->GetModifiedProperties();
		if (!empty($modified)) {
			$cols   = [];
			$types  = [];
			$values = [];
			foreach ($modified as $property => $dbMap) {
				$cols[]                = $dbMap['col'];
				$types[]               = $dbMap['type'];
				$values[$dbMap['col']] = $dbMap['value'];
			}
			$sql = "UPDATE `{$model->GetDbTable()}` SET ";
			foreach ($cols as $col) {
				$sql .= "`$col` = :$col,";
			}
			$sql     = substr($sql, 0, -1); // remove trailing comma
			$sql    .= " WHERE `{$properties[$idProperty]['col']}` = :{$properties[$idProperty]['col']}";
			$types[] = $properties[$idProperty]['type'];

			$values[$properties[$idProperty]['col']] = $properties[$idProperty]['value'];
			$this->writer->executeQuery($sql, $values, $types);
		}
	}

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

		echo $returnWhere;
		return $returnWhere;
	}
}
