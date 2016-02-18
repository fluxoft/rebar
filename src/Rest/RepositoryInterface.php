<?php

namespace Fluxoft\Rebar\Rest;

//use Fluxoft\Rebar\Rest\RepositoryInterface;

/**
 * Class Repository
 * @package Fluxoft\Rebar\Rest
 */
interface RepositoryInterface {
	/**
	 * @param array $params
	 * @param array $filter
	 * @param int $page
	 * @param int $pageSize
	 * @return Reply
	 */
	public function Get(array $params, array $filter = [], $page = 1, $pageSize = 0);

	/**
	 * @param array $post
	 * @return Reply
	 */
	public function Post(array $post = []);

	/**
	 * @param $id
	 * @param array $model
	 * @return Reply
	 */
	public function Put($id, array $model);

	/**
	 * @param $id
	 * @return Reply
	 */
	public function Delete($id);
}
