<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Controller as BaseController;
use Fluxoft\Rebar\Presenters\Json;

abstract class Controller extends BaseController {
	protected $corsEnabled        = false;
	protected $corsDomainsAllowed = [];

	protected function handleAuth(AuthInterface $auth) {
		switch ($this->request->Method) {
			case 'OPTIONS':
				$corsOK = $this->corsCheck($this->request->Headers);
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
				$this->set('auth', ($user === false) ? false : true);
				$this->set('userID', ($user === false) ? null : $user->GetID());
				break;
			case 'POST':
				$body = json_decode($this->request->Body, true);

				$email    = $body['credentials']['username'];
				$password = $body['credentials']['password'];
				$remember = (isset($body['credentials']['remember']) ? $body['credentials']['remember'] : false);
				/** @var \Fluxoft\Rebar\Auth\Db\User $user */
				$authUser = $auth->Login($email, $password, $remember);
				$this->set('auth', ($authUser === false) ? false : true);
				$this->set('userID', ($authUser === false) ? 0 : $authUser->GetID());
				break;
			case 'DELETE':
				$auth->Logout();
				$this->set('auth', false);
				break;
		}
	}

	protected function run(
		RepositoryInterface $repository,
		array $params,
		array $config = null
	) {
		$allowed = (isset($config['allowed']) ? $config['allowed'] : ['GET']);

		// OPTIONS requests must be allowed for CORS capability
		if (!in_array('OPTIONS', $allowed)) {
			array_push($allowed, 'OPTIONS');
		}

		$method   = $this->request->Method;
		$getVars  = $this->request->Get();
		$postVars = $this->request->Post();
		$putVars  = $this->request->Put();
		$body     = $this->request->Body;

		$corsOK = $this->corsCheck($this->request->Headers);

		// Force Json presenter for this type of controller (so all replies are in JSON format)
		// and set its Callback property from the value in $getVars['callback'], then unset that
		// value from the array if it exists.
		$this->presenterClass = 'Json';
		$this->presenter      = new Json();
		$this->presenter->SetCallback($this->request->Get('callback', ''));
		unset($getVars['callback']);

		if (!in_array($method, $allowed)) {
			$response = [403, ['error' => "The {$method} method is not permitted here."]];
		} else {
			switch ($method) {
				case 'OPTIONS':
					if ($corsOK) {
						$response = [200, []];
					} else {
						$response = [403, []];
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
							$response = $repository->GetSet($get, $page, $pageSize);
							break;
						case 1:
							// assume the first params value is the ID of an item
							$response = $repository->GetOne($params[0]);
							break;
						case 2:
							$response = $repository->GetSubset($params[0], $params[1], $page, $pageSize);
							break;
						default:
							$response = [
								400,
								['error' => 'Too many parameters in URL.']
							];
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

					$response = $repository->Post($model);
					break;

				case 'PUT':
					/**
					 * PUT /{item}/{id} <- UPDATE an {item} with ID {id} using POST/PUT params
					 */
					if (empty($params)) {
						$response = [422, ['error' => 'You must specify an ID in order to update.']];
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
						$response = $repository->Put($params[0], $model);
					}
					break;

				case 'DELETE':
					if (empty($params)) {
						// cannot delete if we don't have an id
						$response = [422, ['error' => 'ID is required for DELETE operation.']];
					} else {
						$response = $repository->Delete($params[0]);
					}
					break;

				default:
					$response = [405, ['error' => 'Unsupported method.']];
			}
		}

		if ($response === false) {
			$this->response->Status = 404;
			$this->set('error', 'Not found');
		} elseif (!is_array($response)) {
			$this->response->Status = 500;
			$this->set('error', 'Bad response from repository');
		} else {
			$this->response->Status = $response[0];
			foreach ($response[1] as $key => $value) {
				$this->set($key, $value);
			}
		}
	}

	private function corsCheck(array $headers) {
		$corsOK = true;
		if ($this->corsEnabled) {
			if (isset($headers['Origin'])) {
				$origin = $headers['Origin'];
				if (in_array($origin, $this->corsDomainsAllowed)) {
					$this->response->AddHeader('Access-Control-Allow-Origin', $origin);
					$this->response->AddHeader('Access-Control-Allow-Credentials', 'true');
					$this->response->AddHeader('Access-Control-Allow-Methods', implode(',', $allowed));
					$this->response->AddHeader('Access-Control-Allow-Headers', 'Content-Type');
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
