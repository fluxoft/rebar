<?php

namespace Fluxoft\Rebar\Rest;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Fluxoft\Rebar\Auth\Db\User;
use Fluxoft\Rebar\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Db\Mapper;
use Fluxoft\Rebar\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * Class DataRepository
 * @package Fluxoft\Rebar\Rest
 */
class DataRepository implements RepositoryInterface {
	/** @var Mapper */
	protected $mapper;
	/** @var LoggerInterface */
	protected $logger;
	/** @var User */
	protected $authUser;

	protected $authUserFilter     = false;
	protected $authUserIDProperty = 'UserID';

	/**
	 * @param Mapper $mapper
	 * @param LoggerInterface $logger
	 * @param User $authUser
	 */
	public function __construct(Mapper $mapper, LoggerInterface $logger = null, User $authUser = null) {
		$this->mapper   = $mapper;
		$this->logger   = $logger;
		$this->authUser = $authUser;
	}

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Get(Request $request, $params = []) {
		/**
		 * GET /{item}                 <- retrieve a set
		 * GET /{item}?page={page}     <- retrieve page {page} of results
		 * GET /{item}/{id}            <- retrieve {item} with id {id}
		 * GET /{item}/{id}/{children} <- retrieve the children of {item} with id {id}
		 *     ** the above only works on Mappers which have a Get{children} method accepting {id} as an argument
		 */
		$get      = $request->Get();
		$page     = (isset($get['page']) && is_numeric($get['page'])) ? $get['page'] : 1;
		$pageSize = 0;
		if (isset($config['pageSize']) && is_numeric($config['pageSize'])) {
			$pageSize = $config['pageSize'];
		}
		if (isset($get['pageSize']) && is_numeric($get['pageSize'])) {
			$pageSize = $get['pageSize'];
		}

		unset($get['page']);
		unset($get['pageSize']);
		unset($get['callback']);

		if ($this->authUserFilter && !isset($this->authUser)) {
			return new Reply(403, ['error' => 'Must be logged in to access this resource.']);
		}

		$reply = null;
		switch (count($params)) {
			case 0:
				if ($this->authUserFilter && isset($this->authUser)) {
					$get[$this->authUserIDProperty] = $this->authUser->GetID();
				}

				$order = [];
				if (isset($get['order'])) {
					if (is_array($get['order'])) {
						$order = $get['order'];
					} else {
						$order = [$get['order']];
					}
					unset($get['order']);
				}
				$set = $this->mapper->GetSetWhere($get, $order, $page, $pageSize);

				$reply = new Reply(
					200,
					$set
				);

				break;
			case 1:
				$item = $this->mapper->GetOneById($params[0]);
				if (!isset($item)) {
					$reply = new Reply(
						404,
						[
							'error' => 'The requested item could not be found.'
						]
					);
				} else {
					$reply = new Reply(
						200,
						$item
					);
					if ($this->authUserFilter &&
						$item->{$this->authUserIDProperty} !== $this->authUser->GetID()
					) {
						$reply = new Reply(
							404,
							['error' => 'The requested item could not be found.']
						);
					}
				}
				break;
			case 2:
				$id         = $params[0];
				$subsetName = $params[1];
				$method     = 'Get'.ucwords($subsetName);
				if (!method_exists($this->mapper, $method)) {
					$reply = new Reply(
						404,
						[
							'error' => sprintf('"%s" not found.', $subsetName)
						]
					);
				} else {
					if ($this->authUserFilter) {
						$parent = $this->mapper->GetOneById($id);

						if (!isset($parent) ||
							($parent->{$this->authUserIDProperty} !== $this->authUser->GetID())
						) {
							$reply = new Reply(
								404,
								['error' => 'The requested item could not be found.']
							);
						} else {
							$subset = $this->mapper->$method($parent->GetID(), $page, $pageSize);
							$reply  = new Reply(
								200,
								$subset
							);
						}
					} else {
						$subset = $this->mapper->$method($id, $page, $pageSize);
						if (isset($subset)) {
							$reply = new Reply(
								200,
								$subset
							);
						} else {
							$reply = new Reply(
								404,
								['error' => 'The subset returned a null result.']
							);
						}
					}
				}
				break;
			default:
				$reply = new Reply(
					400,
					['error' => 'Too many parameters in URL.']
				);
				break;
		}
		return $reply;
	}

	/**
	 * POST /{item}/{id} <- CREATE an {item} with ID {id} using POST/PUT params
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Post(Request $request, $params = []) {
		// $params is unused in this implementation
		$params = null;

		$model = $this->getPostData($request);

		if ($this->authUserFilter) {
			if (!isset($this->authUser)) {
				return new Reply(403, ['error' => 'Must be logged in to access this resource.']);
			} else {
				// Just change the $post's UserID to the user's. This will let the attacker add
				// something, but to his own account, not someone else's. This actually has the
				// somewhat dubious side effect of allowing someone to add something without
				// the need to pass in their UserID.
				$model[$this->authUserIDProperty] = $this->authUser->GetID();
			}
		}
		$new = $this->mapper->GetNew();
		foreach ($model as $key => $value) {
			$new->$key = $value;
		}
		try {
			$this->mapper->Save($new);
			$response = new Reply(201, $new);
		} catch (InvalidModelException $e) {
			$response = new Reply(
				422,
				[
					'error' => $e->getMessage(),
					'invalidProperties' => $new->GetValidationErrors()
				]
			);
		} catch (\InvalidArgumentException $e) {
			$response = new Reply(422, ['error' => $e->getMessage()]);
		} catch (UniqueConstraintViolationException $e) {
			$response = new Reply(
				409,
				['error' => 'Object already exists.']
			);
		} catch (DBALException $e) {
			$this->log('error', $e->getMessage());
			$response = new Reply(
				500,
				['error' => 'Database error. Please try again later.']
			);
		} catch (\Exception $e) {
			$response = new Reply(500, ['error' => $e->getMessage()]);
		}
		return $response;
	}

	/**
	 * PUT /{item}/{id} <- UPDATE an {item} with ID {id} using POST/PUT params
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Put(Request $request, $params = []) {
		if (empty($params)) {
			return new Reply(422, ['error' => 'You must specify an ID in order to update.']);
		} else {
			if ($this->authUserFilter && !isset($this->authUser)) {
				return new Reply(403, ['error' => 'Must be logged in to access this resource.']);
			}

			$id = $params[0];
			/** @var \Fluxoft\Rebar\Db\Model $update */
			$update = $this->mapper->GetOneById($id);
			if ($this->authUserFilter) {
				if ($update->{$this->authUserIDProperty} !== $this->authUser->GetID()) {
					$update = false;
				}
			}
			if (!isset($update)) {
				return new Reply(404, ['error' => 'The object to be updated was not found.']);
			} else {
				$errors = [];
				$model  = $this->getPutData($request);

				foreach ($model as $key => $value) {
					try {
						$update->$key = $value;
					} catch (\InvalidArgumentException $e) {
						$errors[] = $e->getMessage();
					}
				}
				if (empty($errors)) {
					try {
						$this->mapper->Save($update);
						return new Reply(200, $update);
					} catch (InvalidModelException $e) {
						return new Reply(
							422,
							[
								'error' => $e->getMessage(),
								'invalidProperties' => $update->GetValidationErrors()
							]
						);
					}
				} else {
					return new Reply(422, ['errors' => $errors]);
				}
			}
		}
	}

	/**
	 * @param Request $request
	 * @param array $params
	 * @return Reply
	 */
	public function Delete(Request $request, $params = []) {
		// $request is unused in this implementation
		$request = null;

		if (empty($params)) {
			// cannot delete if we don't have an id
			return new Reply(422, ['error' => 'ID is required for DELETE operation.']);
		} else {
			if ($this->authUserFilter && !isset($this->authUser)) {
				return new Reply(403, ['error' => 'Must be logged in to access this resource.']);
			}
			$id = $params[0];

			$delete = $this->mapper->GetOneById($id);
			if ($this->authUserFilter) {
				if ($delete->{$this->authUserIDProperty} !== $this->authUser->GetID()) {
					$delete = null;
				}
			}
			if (!isset($delete)) {
				return new Reply(403, ['error' => 'Must be logged in to access this resource.']);
			} else {
				$this->mapper->Delete($delete);
				return new Reply(204, ['success' => 'The item was deleted.']);
			}
		}
	}

	private $postData = null;

	/**
	 * Will return the request's data as an array from whatever source it can find.
	 * Can be called in child classes to modify the contents of the data before saving.
	 * @param Request $request
	 * @return array
	 */
	protected function getPostData(Request $request) {
		if (!isset($this->postData)) {
			$body = $request->Body;
			/** @var array $postVars */
			$postVars = $request->Post();

			if (isset($postVars['model'])) {
				$this->postData = json_decode($postVars['model'], true);
			} elseif (!empty($postVars)) {
				$this->postData = $postVars;
			} elseif (strlen($body) > 0) {
				$this->postData = json_decode($body, true);
			} else {
				$this->postData = [];
			}
		}
		return $this->postData;
	}
	protected function setPostData(array $postData) {
		$this->postData = $postData;
	}

	private $putData = null;

	/**
	 * Will return the request's data as an array from whatever source it can find.
	 * Can be called in child classes to modify the contents of the data before saving.
	 * @param Request $request
	 * @return array
	 */
	protected function getPutData(Request $request) {
		if (!isset($this->putData)) {
			$body    = $request->Body;
			$putVars = $request->Put();

			if (isset($putVars['model'])) {
				$this->putData = json_decode($putVars['model'], true);
			} elseif (!empty($putVars)) {
				$this->putData = $putVars;
			} elseif (strlen($body) > 0) {
				$this->putData = json_decode($body, true);
			} else {
				$this->putData = [];
			}
		}
		return $this->putData;
	}
	protected function setPutData(array $putData) {
		$this->putData = $putData;
	}

	protected function log($type, $message) {
		if (isset($this->logger)) {
			$this->logger->$type($message);
		}
	}
}
