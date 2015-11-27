<?php

namespace Fluxoft\Rebar\Rest;

use Doctrine\DBAL\DBALException;
use Fluxoft\Rebar\Auth\Db\User;
use Fluxoft\Rebar\Db\Mapper;
use Psr\Log\LoggerInterface;

class Repository implements RepositoryInterface {
	/** @var Mapper */
	protected $mapper;
	/** @var LoggerInterface */
	protected $logger;
	/** @var User */
	protected $authUser;

	protected $authUserFilter     = false;
	protected $authUserIDProperty = 'UserID';

	public function __construct(Mapper $mapper, LoggerInterface $logger = null, User $authUser = null) {
		$this->mapper   = $mapper;
		$this->logger   = $logger;
		$this->authUser = $authUser;
	}

	public function GetSet(array $filter = [], $page = 1, $pageSize = 0) {
		if ($this->authUserFilter) {
			if (!isset($this->authUser)) {
				return [403, ['error' => 'Must be logged in to access this resource.']];
			}

			// Filter results for this user.
			$filter[$this->authUserIDProperty] = $this->authUser->GetID();
		}

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
		if ($this->authUserFilter && !isset($this->authUser)) {
			return [403, ['error' => 'Must be logged in to access this resource.']];
		}
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
			if ($this->authUserFilter &&
				$item->{$this->authUserIDProperty} !== $this->authUser->GetID()
			) {
				$response = [
					404,
					['error' => 'The requested item could not be found.']
				];
			}
		}
		return $response;
	}

	public function GetSubset($id, $subsetName, $page = 1, $pageSize = 0) {
		if ($this->authUserFilter && !isset($this->authUser)) {
			return [403, ['error' => 'Must be logged in to access this resource.']];
		}
		$method = 'Get'.ucwords($subsetName);
		if (!method_exists($this->mapper, $method)) {
			$response = [
				404,
				[
					'error' => sprintf('"%s" not found.', $subsetName)
				]
			];
		} else {
			if ($this->authUserFilter) {
				$parent = $this->mapper->GetOneById($id);
				if ($parent->{$this->authUserIDProperty} !== $this->authUser->GetID()) {
					$response = [
						404,
						['error' => 'The requested item could not be found.']
					];
				} else {
					$subset   = $this->mapper->$method($parent->GetID(), $page, $pageSize);
					$response = [
						200,
						$subset

					];
				}
			} else {
				$subset   = $this->mapper->$method($id, $page, $pageSize);
				$response = [
					200,
					$subset

				];
			}
		}
		return $response;
	}

	public function Post(array $post = []) {
		if ($this->authUserFilter) {
			if (!isset($this->authUser)) {
				return [403, ['error' => 'Must be logged in to access this resource.']];
			} else {
				// Just change the $post's UserID to the user's. This will let the attacker add
				// something, but to his own account, not someone else's. This actually has the
				// somewhat dubious side effect of allowing someone to add something without
				// the need to pass in their UserID.
				$post[$this->authUserIDProperty] = $this->authUser->GetID();
			}
		}
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
		if ($this->authUserFilter && !isset($this->authUser)) {
			return [403, ['error' => 'Must be logged in to access this resource.']];
		}
		/** @var \Fluxoft\Rebar\Db\Model $update */
		$update = $this->mapper->GetOneById($id);
		if ($this->authUserFilter) {
			if ($update->{$this->authUserIDProperty} !== $this->authUser->GetID()) {
				$update = false;
			}
		}
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
		if ($this->authUserFilter && !isset($this->authUser)) {
			return [403, ['error' => 'Must be logged in to access this resource.']];
		}
		$delete = $this->mapper->GetOneById($id);
		if ($this->authUserFilter) {
			if ($delete->{$this->authUserIDProperty} !== $this->authUser->GetID()) {
				$delete = false;
			}
		}
		if ($delete === false) {
			return [403, ['error' => 'Must be logged in to access this resource.']];
		} else {
			$this->mapper->Delete($delete);
			return [204, ['success' => 'The item was deleted.']];
		}
	}

	protected function log($type, $message) {
		if (isset($this->logger)) {
			$this->logger->$type($message);
		}
	}
}
