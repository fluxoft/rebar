<?php

namespace Fluxoft\Rebar\Rest;

interface RepositoryInterface {
	public function GetSet (array $filter = [], $page = 1, $pageSize = 0);
	public function GetOne ($id);
	public function GetSubset ($id, $subsetName, $page = 1, $pageSize = 0);

	public function Post (array $post = []);

	public function Put ($id, array $model);

	public function Delete ($id);
}
