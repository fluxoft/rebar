<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;
use Fluxoft\Rebar\Data\FilterInterface;
use Fluxoft\Rebar\Data\ServiceInterface;
use Fluxoft\Rebar\Data\SortInterface;
use Fluxoft\Rebar\Model;

abstract class AbstractService implements ServiceInterface {
	/** @var MapperInterface */
	protected MapperInterface $mapper;

	public function __construct(MapperInterface $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @inheritDoc
	 */
	public function Fetch(mixed $id): Model {
		$model = $this->mapper->GetOneById($id);
		if (!$model) {
			throw new \InvalidArgumentException("Model with ID $id not found.");
		}
		return $model;
	}

	/**
	 * @inheritDoc
	 */
	public function FetchAll(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 20): array {
		$filters = $this->buildFilters($filters);
		$sort    = $this->buildSort($sort);
		return $this->mapper->GetSet($filters, $sort, $page, $pageSize);
	}

	/**
	 * @inheritDoc
	 */
	public function CountAll(array $filters = []): int {
		return $this->mapper->Count($filters);
	}

	/**
	 * @inheritDoc
	 */
	public function Create(array $data): Model {
		$model = $this->mapper->GetNew();
		foreach ($data as $key => $value) {
			if ($model->HasProperty($key)) {
				$model->$key = $value;
			}
		}
		$this->mapper->Save($model);
		return $model;
	}

	/**
	 * @inheritDoc
	 */
	public function Update(mixed $id, array $data): Model {
		$model = $this->Fetch($id);
		foreach ($data as $key => $value) {
			if ($model->HasProperty($key)) {
				$model->$key = $value;
			}
		}
		$this->mapper->Save($model);
		return $model;
	}

	/**
	 * @inheritDoc
	 */
	public function Delete(int $id): void {
		$model = $this->Fetch($id);
		$this->mapper->Delete($model);
	}

	protected const OPERATOR_MAP = [
		'eq' => '=',
		'neq' => '<>',
		'lt' => '<',
		'lte' => '<=',
		'gt' => '>',
		'gte' => '>=',
		'like' => 'LIKE',
		'in' => 'IN',
		'notin' => 'NOT IN',
		'isnull' => 'IS',
		'notnull' => 'IS NOT',
		'between' => 'BETWEEN'
	];

	/**
	 * @param array $rawFilters
	 * @return FilterInterface[]
	 */
	protected function buildFilters(array $rawFilters): array {
		$filters = [];
		foreach ($rawFilters as $property => $criteria) {
			if (is_array($criteria)) {
				// Example: filter[Size][gte]=300
				foreach ($criteria as $operatorKey => $value) {
					$operatorKey = strtolower($operatorKey);
					$operator    = self::OPERATOR_MAP[$operatorKey]
						?? throw new \InvalidArgumentException("Invalid operator '$operatorKey' for property '$property'.");

					if ($operator === 'IN' ||
						$operator === 'NOT IN' ||
						$operator === 'BETWEEN'
					) {
						$value = explode('|', $value);
					}
					if ($operator === 'BETWEEN' && count($value) !== 2) {
						throw new \InvalidArgumentException('BETWEEN operator requires two values separated by a pipe.');
					}
					if ($operator === 'IS' || $operator === 'IS NOT') {
						$value = null;
					}
					$filters[] = new Filter($property, $operator, $value);
				}
			} else {
				$filters[] = new Filter($property, '=', $criteria);
			}
		}
		return $filters;
	}

	/**
	 * @param array $rawSort
	 * @return SortInterface[]
	 */
	protected function buildSort(array $rawSort): array {
		$sort = [];
		foreach ($rawSort as $sortField) {
			if (str_starts_with($sortField, '-')) {
				$sort[] = new Sort(substr($sortField, 1), 'DESC');
			} else {
				$sort[] = new Sort($sortField, 'ASC');
			}
		}
		return $sort;
	}
}
