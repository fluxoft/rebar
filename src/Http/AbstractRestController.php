<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Container;
use Fluxoft\Rebar\Data\ServiceInterface;

abstract class AbstractRestController extends Controller {
	protected ServiceInterface $service;

	/**
	 * Sets up the service dependency using the container.
	 * @param Container $container
	 */
	public function Setup(Container $container): void {
		$serviceClass = $this->getServiceClass();
		if (!interface_exists(ServiceInterface::class) &&
			!class_exists($serviceClass)) {
			throw new \LogicException("Service class $serviceClass does not exist.");
		}
		$this->service = $container[$serviceClass];
	}

	/**
	 * Define the fully-qualified service class name in the concrete controller.
	 * @return string
	 */
	abstract protected function getServiceClass(): string;

	/**
	 * Handles RESTful requests and routes them based on HTTP methods and parameters.
	 *
	 * ### Supported Query Parameters
	 *
	 * - **Filters**: Use the `filter` parameter to apply filters to the result set. Each filter specifies a property,
	 *   an operator, and a value. Example:
	 *   - `?filter[Color][eq]=blue` (Filters items where `Color` is equal to `blue`.)
	 *   - `?filter[Size][gte]=10` (Filters items where `Size` is greater than or equal to `10`.)
	 *   - `?filter[Price][between]=10|20` (Filters items where `Price` is between `10` and `20`.)
	 *
	 * - **Sorting**: Use the `sort` parameter to specify sorting criteria. Sorting can include multiple fields, separated by commas.
	 *   - `?sort=Name` (Sorts by `Name` in ascending order.)
	 *   - `?sort=-CreatedAt` (Sorts by `CreatedAt` in descending order. Use a `-` prefix for descending order.)
	 *
	 * - **Pagination**: Use `page[number]` and `page[size]` for pagination:
	 *   - `?page[number]=1&page[size]=25` (Fetches the first page with 25 results per page.)
	 *   - Alternatively, `page` and `pageSize` can be used: `?page=1&pageSize=25`.
	 *
	 * ### HTTP Methods
	 *
	 * - `GET`: Fetches resources. If an ID is provided in the path, fetches a single resource.
	 * - `POST`: Creates a new resource. Expects data in the request body.
	 * - `PUT`: Updates an existing resource. Expects an ID in the path and data in the request body.
	 * - `DELETE`: Deletes a resource. Expects an ID in the path.
	 *
	 * @param mixed $id Optional ID parameter for fetching, updating, or deleting a specific resource.
	 * @return void
	 */
	public function Default(mixed $id = null): void {
		$responseData = [];
		$status       = 200;

		try {
			switch ($this->request->Method) {
				case 'GET':
					if ($id !== null) {
						// Fetch single model
						$responseData = ['data' => $this->service->Fetch($id)];
					} else {
						$pageParams = $this->request->Get->Get('page', []);
						$page       = (int) ($pageParams['number'] ?? $this->request->Get->Get('page', 1));
						$pageSize   = (int) ($pageParams['size'] ?? $this->request->Get->Get('pageSize', 20));

						$filterParams = $this->request->Get->Get('filter', []);
						$sortParams   = $this->request->Get->Get('sort', []);
						
						$responseData = [
							'data' => $this->service->FetchAll($filterParams, $sortParams, $page, $pageSize),
							'meta' => [
								'total' => $this->service->CountAll($filterParams),
								'page' => $page,
								'pageSize' => $pageSize,
							],
						];
					}
					break;

				case 'POST':
					$data         = $this->request->Post->Get();
					$responseData = ['data' => $this->service->Create($data)];
					$status       = 201;
					break;

				case 'PUT':
					if ($id === null) {
						throw new \InvalidArgumentException('ID parameter is required for PUT.');
					}
					$data         = $this->request->Post->Get();
					$responseData = ['data' => $this->service->Update($id, $data)];
					break;

				case 'DELETE':
					if ($id === null) {
						throw new \InvalidArgumentException('ID parameter is required for DELETE.');
					}
					$this->service->Delete($id);
					$responseData = ['message' => 'Resource deleted successfully.'];
					$status       = 204;
					break;

				default:
					throw new \InvalidArgumentException("Unsupported HTTP method {$this->request->Method}.");
			}
		} catch (\InvalidArgumentException $e) {
			$responseData = ['error' => $e->getMessage()];
			$status       = 400;
		} catch (\Exception $e) {
			$responseData = ['error' => 'An unexpected error occurred.'];
			$status       = 500;
		}

		$this->response->Status = $status;
		foreach ($responseData as $key => $value) {
			$this->set($key, $value);
		}
	}
}
