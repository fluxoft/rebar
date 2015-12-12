<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Controller as BaseController;
use Fluxoft\Rebar\Presenters\Json;

abstract class Controller extends BaseController {
	protected $corsEnabled        = false;
	protected $corsDomainsAllowed = [];

	protected function handleAuth(AuthInterface $auth) {
		$allowedMethods = ['GET', 'POST', 'DELETE', 'OPTIONS'];
		$corsOK         = $this->corsCheck($this->request->Headers, $allowedMethods);
		
		// Force Json presenter for this type of controller (so all replies are in JSON format)
		// and set its Callback property from the value in $getVars['callback'], then unset that
		// value from the array if it exists.
		$this->presenterClass = 'Json';
		$this->presenter      = new Json();
		$this->presenter->SetCallback($this->request->Get('callback', ''));
		$getVars = $this->request->Get();
		unset($getVars['callback']);

		switch ($this->request->Method) {
			case 'OPTIONS':
				if ($corsOK) {
					$this->response->Status = 200;
					$this->set('success', true);
				} else {
					$this->response->Status = 403;
					$this->set('error', 'Not allowed.');
				}
				break;
			case 'GET':
				/** @var \Fluxoft\Rebar\Auth\Db\User $user */
				$user = $auth->GetAuthenticatedUser();
				$this->set('auth', isset($user));
				$this->set('user', $user);
				break;
			case 'POST':
				$body = json_decode($this->request->Body, true);

				$email    = $body['credentials']['username'];
				$password = $body['credentials']['password'];
				$remember = (isset($body['credentials']['remember']) ? $body['credentials']['remember'] : false);
				/** @var \Fluxoft\Rebar\Auth\Db\User $authUser */
				$user = $auth->Login($email, $password, $remember);
				$this->set('auth', isset($user));
				$this->set('user', $user);
				break;
			case 'DELETE':
				$auth->Logout();
				$this->set('auth', false);
				break;
		}
	}

	protected function run(
		Repository $repository,
		array $params,
		array $config = []
	) {
		$allowedMethods = (isset($config['allowed']) ? $config['allowed'] : ['GET']);

		// OPTIONS requests must be allowed for CORS capability
		if (!in_array('OPTIONS', $allowedMethods)) {
			array_push($allowedMethods, 'OPTIONS');
		}

		$method   = $this->request->Method;
		$getVars  = $this->request->Get();
		$postVars = $this->request->Post();
		$putVars  = $this->request->Put();
		$body     = $this->request->Body;

		$corsOK = $this->corsCheck($this->request->Headers, $allowedMethods);

		// Force Json presenter for this type of controller (so all replies are in JSON format)
		// and set its Callback property from the value in $getVars['callback'], then unset that
		// value from the array if it exists.
		$this->presenterClass = 'Json';
		$this->presenter      = new Json();
		$this->presenter->SetCallback($this->request->Get('callback', ''));
		unset($getVars['callback']);

		$reply = new Reply();

		if (!in_array($method, $allowedMethods)) {
			$reply->Status = 403;
			$reply->Data   = ['error' => "The {$method} method is not permitted here."];
		} else {
			switch ($method) {
				case 'OPTIONS':
					if ($corsOK) {
						$reply->Status = 200;
					} else {
						$reply->Status = 403;
					}
					break;
				case 'GET':
					/**
					 * GET /{item}                 <- retrieve a set
					 * GET /{item}?page={page}     <- retrieve page {page} of results
					 * GET /{item}/{id}            <- retrieve {item} with id {id}
					 * GET /{item}/{id}/{children} <- retrieve the children of {item} with id {id}
					 *     ** the above only works on Mappers which have a Get{children} method accepting {id} as an argument
					 */
					$get      = $getVars;
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

					if (!isset($params[0])) {
						$params = [];
					} elseif (!isset($params[1])) {
						$params = [$params[0]];
					} elseif (!isset($params[2])) {
						$params = [$params[0], $params[1]];
					}

					switch (count($params)) {
						case 0:
							$reply = $repository->GetSet($get, $page, $pageSize);
							break;
						case 1:
							// assume the first params value is the ID of an item
							$reply = $repository->GetOne($params[0]);
							break;
						case 2:
							$reply = $repository->GetSubset($params[0], $params[1], $page, $pageSize);
							break;
						default:
							$reply->Status = 400;
							$reply->Data   = ['error' => 'Too many parameters in URL.'];
							break;
					}
					break;

				case 'POST':
					/**
					 * POST /{items} <- create an {item} using POST data
					 */
					if (isset($postVars['model'])) {
						$model = json_decode($postVars['model'], true);
					} elseif (!empty($postVars)) {
						$model = $postVars;
					} elseif (strlen($body) > 0) {
						$model = json_decode($body, true);
					} else {
						$model = [];
					}

					$reply = $repository->Post($model);
					break;

				case 'PUT':
					/**
					 * PUT /{item}/{id} <- UPDATE an {item} with ID {id} using POST/PUT params
					 */
					if (empty($params)) {
						$reply->Status = 422;
						$reply->Data   = ['error' => 'You must specify an ID in order to update.'];
					} else {
						if (isset($putVars['model'])) {
							$model = json_decode($putVars['model'], true);
						} elseif (!empty($putVars)) {
							$model = $putVars;
						} elseif (strlen($body) > 0) {
							$model = json_decode($body, true);
						} else {
							$model = [];
						}
						$reply = $repository->Put($params[0], $model);
					}
					break;

				case 'DELETE':
					if (empty($params)) {
						// cannot delete if we don't have an id
						$reply->Status = 422;
						$reply->Data   = ['error' => 'ID is required for DELETE operation.'];
					} else {
						$reply = $repository->Delete($params[0]);
					}
					break;

				default:
					$reply->Status = 405;
					$reply->Data   = ['error' => 'Unsupported method.'];
			}
		}

		if ($reply instanceof Reply) {
			$this->response->Status = $reply->Status;
			foreach ($reply->Data as $key => $value) {
				$this->set($key, $value);
			}
		} else {
			$this->response->Status = 500;
			$this->set('error', 'Bad reply from repository');
		}
	}

	private function corsCheck(array $headers, array $allowedMethods) {
		$corsOK = true;
		if ($this->corsEnabled) {
			if (isset($headers['Origin'])) {
				$allowedHeaders = (isset($headers['Access-Control-Request-Headers']) ?
					$headers['Access-Control-Request-Headers'] : '');
				$origin         = $headers['Origin'];
				if (in_array($origin, $this->corsDomainsAllowed)) {
					$this->response->AddHeader('Access-Control-Allow-Origin', $origin);
					$this->response->AddHeader('Access-Control-Allow-Credentials', 'true');
					$this->response->AddHeader('Access-Control-Allow-Methods', implode(',', $allowedMethods));
					$this->response->AddHeader('Access-Control-Allow-Headers', $allowedHeaders);
				} else {
					$corsOK = false;
				}
			} else {
				$corsOK = false;
			}
		}
		return $corsOK;
	}
}
