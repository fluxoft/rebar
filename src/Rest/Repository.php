<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Rest\RepositoryInterface;

/**
 * Class Repository
 * @package Fluxoft\Rebar\Rest
 */
abstract class Repository {
	/**
	 * @param array $filter
	 * @param int $page
	 * @param int $pageSize
	 * @return Reply
	 */
	abstract public function GetSet(array $filter = [], $page = 1, $pageSize = 0);

	/**
	 * @param $id
	 * @return Reply
	 */
	abstract public function GetOne($id);

	/**
	 * @param $id
	 * @param $subsetName
	 * @param int $page
	 * @param int $pageSize
	 * @return Reply
	 */
	abstract public function GetSubset($id, $subsetName, $page = 1, $pageSize = 0);

	/**
	 * @param array $post
	 * @return Reply
	 */
	abstract public function Post(array $post = []);

	/**
	 * @param $id
	 * @param array $model
	 * @return Reply
	 */
	abstract public function Put($id, array $model);

	/**
	 * @param $id
	 * @return Reply
	 */
	abstract public function Delete($id);
}
