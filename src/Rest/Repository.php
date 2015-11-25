<?php

namespace Fluxoft\Rebar\Rest;

use Doctrine\DBAL\DBALException;
use Fluxoft\Rebar\Db\Mapper;
use Psr\Log\LoggerInterface;

class Repository implements RepositoryInterface {
	protected $mapper;
	protected $logger;

	public function __construct(Mapper $mapper, LoggerInterface $logger = null) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	public function GetSet(array $filter = [], $page = 1, $pageSize = 0) {
		// if the params array was empty, return a set
		$filterKeys   = [];
		$filterValues = [];
		foreach ($filter as $key => $value) {
			$filterKeys[]          = '{'.$key.'} = :'.$key;
			$filterValues[":$key"] = $value;
		}

		$whereClause = implode(' AND ', $filterKeys);

		$set = $this->mapper->GetSetWhere($whereClause, $filterValues, $page, $pageSize);

		return [
			200,
			$set
		];
	}

	public function GetOne($id) {
		$item = $this->mapper->GetOneById($id);
		if ($item === false) {
			$response = [
				404,
				[
					'error' => 'The requested item could not be found.'
				]
			];
		} else {
			$response = [
				200,
				$item
			];
		}
		return $response;
	}

	public function GetSubset($id, $subsetName, $page = 1, $pageSize = 0) {
		$method = 'Get'.ucwords($subsetName);
		if (!method_exists($this->mapper, $method)) {
			$response = [
				404,
				[
					'error' => sprintf('"%s" not found.', $subsetName)
				]
			];
		} else {
			$subset   = $this->mapper->$method($id, $page, $pageSize);
			$response = [
				200,
				$subset
			];
		}
		return $response;
	}

	public function Post(array $post = []) {
		try {
			$new = $this->mapper->GetNew();
			foreach ($post as $key => $value) {
				$new->$key = $value;
			}
			$this->mapper->Save($new);
			$response = [201, $new];
		} catch (\InvalidArgumentException $e) {
			$response = [422, ['error' => $e->getMessage()]];
		} catch (DBALException $e) {
			$this->log('error', $e->getMessage());
			$response = [
				500,
				['error' => 'Database error. Please try again later.']
			];
		} catch (\Exception $e) {
			$response = [500, ['error' => $e->getMessage()]];
		}
		return $response;
	}

	public function Put($id, array $model) {
		/** @var \Fluxoft\Rebar\Db\Model $update */
		$update = $this->mapper->GetOneById($id);
		if ($update === false) {
			$response = [404, ['error' => 'The object to be updated was not found.']];
		} else {
			$errors = [];
			foreach ($model as $key => $value) {
				try {
					$update->$key = $value;
				} catch (\InvalidArgumentException $e) {
					$errors[] = $e->getMessage();
				}
			}
			if (empty($errors)) {
				$this->mapper->Save($update);
				$response = [200, $update];
			} else {
				$response = [422, ['errors' => $errors]];
			}
		}
		return $response;
	}

	public function Delete($id) {
		$this->mapper->DeleteOneById($id);
		$response = [204, ['success' => 'The item was deleted.']];
		return $response;
	}

	protected function log($type, $message) {
		if (isset($this->logger)) {
			$this->logger->$type($message);
		}
	}
}
