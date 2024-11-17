<?php

namespace Fluxoft\Rebar\Db\Mappers;

use Fluxoft\Rebar\Model;

interface MapperInterface {
	public function GetNew(): Model;
	public function GetId();
	public function GetOneById(int $id): ?Model;
	public function GetOne(array $filters): ?Model;
	public function GetSet(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 0): array;
	public function Count(array $filters = []): int;
	public function Delete(Model &$model): void;
	public function DeleteById(int $id): void;
	public function DeleteOneWhere(array $filters): void;
	public function Save(Model &$model): void;
}
