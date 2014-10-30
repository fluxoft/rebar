<?php
namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Db\Exceptions\ModelException;

/**
 * @property \Fluxoft\Rebar\Db\Providers\Provider Reader
 * @property \Fluxoft\Rebar\Db\Providers\Provider Writer
 */
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

	/** @var string the name of the model for which this factory will return instances. */
	protected $model;
	/** @var string Optional namespace for models. */
	protected $namespace;
	/** @var string Concatenated namespace and model. */
	protected $namespacedModel;

	public function __construct(
		Providers\Provider $reader,
		Providers\Provider $writer,
		$model,
		$namespace = ''
	) {
		$this->reader          = $reader;
		$this->writer          = $writer;
		$this->model           = $model;
		$this->namespace       = $namespace;
		$this->namespacedModel = $namespace.$model;
	}

	/**
	 * Return a new (blank) model.
	 * @return Model
	 */
	public function GetNew() {
		return $this->GetOneById($this->namespacedModel, 0);
	}

	/**
	 * Return a Model with ID property of $id.
	 * @param $id
	 * @return mixed
	 * @throws ModelException
	 */
	public function GetOneById($id) {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		return new $this->namespacedModel($this, $id);
	}

	/**
	 * Return a single Model of class $modelClass selected with $where.
	 * @param string $where
	 * @return Model
	 * @throws ModelException
	 */
	public function GetOneWhere($where) {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		/** @var Model $model */
		$model    = new $this->namespacedModel($this);
		$modelSet = $model->GetSet($where, '', 1, 1);
		return $modelSet[0];
	}

	/**
	 * Return an array of Model objects of type $modelClass selected with $filter, sorted by $sort,
	 * and limited to page $page where pages are $pageSize long.
	 * @param string $filter
	 * @param string $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return array Model
	 * @throws ModelException
	 */
	public function GetSet($filter = '', $sort = '', $page = 1, $pageSize = 0) {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		/** @var Model $model */
		$model = new $this->namespacedModel($this);
		return $model->GetSet($filter, $sort, $page, $pageSize);
	}

	/**
	 * Return a count of the models found when queried with $filter.
	 * @param string $filter
	 * @return mixed
	 * @throws ModelException
	 */
	public function CountWhere($filter = '') {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		/** @var Model $model */
		$model = new $this->namespacedModel($this);
		return $model->Count($filter);
	}

	/**
	 * Given the name of a model and an array of data rows, will return a set of objects
	 * populated with the data set. Used for easily retrieving a set of objects from the
	 * results returned by a custom query.
	 * @param array $dataSet
	 * @return Model[]
	 * @throws ModelException
	 */
	public function GetSetFromDataSet(array $dataSet) {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		/** @var Model $model */
		$model = new $this->namespacedModel($this);
		return $model->GetFromDataSet($dataSet);
	}

	/**
	 * Delete the Model of type $modelClass with ID property of $id.
	 * @param mixed $id
	 * @throws ModelException
	 */
	public function DeleteById($id) {
		if (!class_exists($this->namespacedModel)) {
			throw new ModelException(sprintf(
				'The model %s was not found.',
				$this->namespacedModel
			));
		}
		/** @var Model $model */
		$model = new $this->namespacedModel($this);
		$model->Delete($id);
	}

	public function __get($var) {
		switch ($var) {
			case 'Reader':
				$rtn = $this->reader;
				break;
			case 'Writer':
				$rtn = $this->writer;
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Cannot get property "%s"', $var));
				break;
		}
		return $rtn;
	}
}