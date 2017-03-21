<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Http\Request;

/**
 * Class Repository
 * @package Fluxoft\Rebar\Rest
 */
interface RepositoryInterface {
	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Get(Request $request, $params = []);

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Post(Request $request, $params = []);

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Put(Request $request, $params = []);

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Patch(Request $request, $params = []);

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Delete(Request $request, $params = []);
}
