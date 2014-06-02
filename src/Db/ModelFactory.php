<?php
namespace Fluxoft\Rebar\Db;

class ModelFactory {
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
		Providers\Provider $reader,
		Providers\Provider $writer
	) {
		$this->reader = $reader;
		$this->writer = $writer;
	}

	/**
	 * Return a Model of class $modelClass with ID property of $id.
	 * @param string $modelClass
	 * @param string $id
	 * @return Model
	 */
	public function GetOneById($modelClass, $id) {
		return new $modelClass($this->reader, $this->writer, $id);
	}

	/**
	 * Return a single Model of class $modelClass selected with $where.
	 * @param string $modelClass
	 * @param string $where
	 * @return Model
	 */
	public function GetOneWhere($modelClass, $where) {
		$model = new $modelClass($this->reader, $this->writer);
		$modelSet = $model->GetAll($where, '', 1, 1);
		return $modelSet[0];
	}

	/**
	 * Return an array of Model objects of type $modelClass selected with $filter, sorted by $sort,
	 * and limited to page $page where pages are $pageSize long.
	 * @param string $modelClass
	 * @param string $filter
	 * @param string $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return array Model
	 */
	public function GetSet($modelClass, $filter = '', $sort = '', $page = 1, $pageSize = 0) {
		$model = new $modelClass($this->reader, $this->writer);
		return $model->GetAll($filter, $sort, $page, $pageSize);
	}

	/**
	 * Delete the Model of type $modelClass with ID property of $id.
	 * @param string $modelClass
	 * @param mixed $id
	 */
	public function DeleteById($modelClass, $id) {
		$model = new $modelClass($this->reader, $this->writer);
		$model->Delete($id);
	}
} 