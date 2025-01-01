<?php

namespace Fluxoft\Rebar\Data;

use Fluxoft\Rebar\Data\FilterInterface;
use Fluxoft\Rebar\Data\SortInterface;
use Fluxoft\Rebar\Model;

interface ServiceInterface {
	/**
	 * Fetch a single model by its ID.
	 *
	 * @param mixed $id
	 * @return Model
	 */
	public function Fetch(mixed $id): Model;

	/**
	 * Fetch a list of models with optional filters, sorting, and pagination.
	 * 
	 * Filters allow clients to narrow down the result set by specifying conditions on properties.
	 * Filters are specified as key-value pairs, where the key is the property to filter by,
	 * and the value is either a single value or an array of operator/value pairs.
	 * Example filter structure:
	 * [
	 *     'Size' => ['gte' => 10], // Size >= 10
	 *     'Color' => 'Blue',       // Color = 'Blue'
	 *     'Price' => ['lt' => 100, 'gt' => 50] // 50 < Price < 100
	 * ]
	 * 
	 * Sort parameters allow clients to define the order of the results.
	 * Sort parameters are specified as an array of property names, optionally prefixed with `-` for descending order.
	 * Example sort structure:
	 * [
	 *     '-Price', // Sort by Price descending
	 *     'Name'    // Sort by Name ascending
	 * ]
	 *
	 * Pagination is controlled via the `$page` and `$pageSize` parameters.
	 * `$page` specifies the current page (starting from 1), and `$pageSize` specifies the number of results per page.
	 *
	 * @param FilterInterface[] $filters
	 * @param SortInterface[] $sort
	 * @param int $page
	 * @param int $pageSize
	 * @return Model[]
	 */
	public function FetchAll(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 20): array;

	/**
	 * Count the total number of models matching optional filters.
	 * 
	 * Filters are specified in the same way as for FetchAll.
	 *
	 * @param FilterInterface[] $filters
	 * @return int
	 */
	public function CountAll(array $filters = []): int;

	/**
	 * Create a new model using the provided data.
	 *
	 * @param array $data
	 * @return Model
	 */
	public function Create(array $data): Model;

	/**
	 * Update an existing model by its ID using the provided data.
	 *
	 * @param mixed $id
	 * @param array $data
	 * @return Model
	 */
	public function Update(mixed $id, array $data): Model;

	/**
	 * Delete a model by its ID.
	 *
	 * @param int $id
	 * @return void
	 */
	public function Delete(int $id): void;
}
