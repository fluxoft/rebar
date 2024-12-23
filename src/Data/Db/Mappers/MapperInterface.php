<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\Filter;
use Fluxoft\Rebar\Data\Db\Sort;
use Fluxoft\Rebar\Model;

interface MapperInterface {
	public function GetNew(): Model;
	public function GetOneById(int $id): ?Model;
	/**
	 * @param Filter[] $filters
	 * @return ?Model
	 */
	public function GetOne(array $filters): ?Model;
	/**
	 * @param Filter[] $filters
	 * @param Sort[] $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return Model[]
	 */
	public function GetSet(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array;
	/**
	 * @param Filter[] $filters
	 * @return int
	 */
	public function Count(array $filters = []): int;
	public function Delete(Model &$model): void;
	public function DeleteById(int $id): void;
	/**
	 * @param Filter[] $filters
	 */
	public function DeleteOneWhere(array $filters): void;
	public function Save(Model $model): void;
}
