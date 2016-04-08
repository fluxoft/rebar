<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Controller as BaseController;
use Fluxoft\Rebar\Presenters\Json;

abstract class Controller extends BaseController {
	protected $corsEnabled        = false;
	protected $corsDomainsAllowed = [];

	/**
	 * @param array $allowedMethods
	 * @return bool|Reply
	 */
	protected function preFlight(array $allowedMethods) {
		// allows allow OPTIONS requests
		if (!in_array('OPTIONS', $allowedMethods)) {
			array_push($allowedMethods, 'OPTIONS');
		}

		// set CORS headers if configured
		$corsOK = $this->corsCheck($this->request->Headers, $allowedMethods);

		$preFlightCheck = true;
		// if this is an OPTIONS request, we just need to make sure corsCheck is true
		if ($this->request->Method === 'OPTIONS') {
			if ($corsOK) {
				$preFlightCheck = new Reply(
					200,
					['success' => true]
				);
			} else {
				$preFlightCheck = new Reply(403, ['error' => 'Not allowed']);
			}
		}
		if (!in_array($this->request->Method, $allowedMethods)) {
			$preFlightCheck = new Reply(
				403,
				['error' => "The {$this->request->Method} method is not permitted here."]
			);
		}
		return $preFlightCheck;
	}

	protected function handleAuth(AuthInterface $auth) {
		$allowedMethods = ['GET', 'POST', 'DELETE', 'OPTIONS'];
		$preFlightCheck = $this->preFlight($allowedMethods);

		if ($preFlightCheck !== true) {
			if ($preFlightCheck instanceof Reply) {
				$this->response->Status = $preFlightCheck->Status;
				foreach ($preFlightCheck->Data as $key => $value) {
					$this->set($key, $value);
				}
			} else {
				$this->response->Status = 500;
				$this->set('error', 'Pre-flight failed to pass, but did not return a readable Reply');
			}
		} else {
			// Force Json presenter for this type of controller (so all replies are in JSON format)
			// and set its Callback property from the value in $getVars['callback'], then unset that
			// value from the array if it exists.
			$this->presenterClass = 'Json';
			$this->presenter      = new Json();
			$this->presenter->SetCallback($this->request->Get('callback', ''));
			$getVars = $this->request->Get();
			unset($getVars['callback']);

			switch ($this->request->Method) {
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
					try {
						/** @var \Fluxoft\Rebar\Auth\Db\User $authUser */
						$user = $auth->Login($email, $password, $remember);
						$this->set('auth', isset($user));
						$this->set('user', $user);
					} catch (UserNotFoundException $e) {
						$this->response->Status = 403;
						$this->set('error', $e->getMessage());
					} catch (InvalidPasswordException $e) {
						$this->response->Status = 403;
						$this->set('error', $e->getMessage());
					} catch (\Exception $e) {
						$this->response->Status = 500;
						$this->set('error', $e->getMessage());
					}
					break;
				case 'DELETE':
					$auth->Logout();
					$this->set('auth', false);
					break;
			}
		}
	}

	protected function run(
		RepositoryInterface $repository,
		array $params,
		array $config = []
	) {
		$params         = array_values($params);
		$allowedMethods = (isset($config['allowed']) ? $config['allowed'] : ['GET']);
		$preFlightCheck = $this->preFlight($allowedMethods);

		if ($preFlightCheck !== true) {
			if ($preFlightCheck instanceof Reply) {
				$this->response->Status = $preFlightCheck->Status;
				foreach ($preFlightCheck->Data as $key => $value) {
					$this->set($key, $value);
				}
			} else {
				$this->response->Status = 500;
				$this->set('error', 'Pre-flight failed to pass, but did not return a readable Reply');
			}
		} else {

			$method   = $this->request->Method;
			$getVars  = $this->request->Get();
			$postVars = $this->request->Post();
			$putVars  = $this->request->Put();
			$body     = $this->request->Body;

			// Force Json presenter for this type of controller (so all replies are in JSON format)
			// and set its Callback property from the value in $getVars['callback'], then unset that
			// value from the array if it exists.
			$this->presenterClass = 'Json';
			$this->presenter      = new Json();
			$this->presenter->SetCallback($this->request->Get('callback', ''));
			unset($getVars['callback']);

			$reply = new Reply();

			switch ($method) {
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

					$reply = $repository->Get($params, $get, $page, $pageSize);

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
