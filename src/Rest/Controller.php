<?php

namespace Fluxoft\Rebar\Rest;

use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Controller as BaseController;
use Fluxoft\Rebar\Presenters\Json;

abstract class Controller extends BaseController {
	protected function handleAuth(AuthInterface $auth) {
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
				try {
					/** @var \Fluxoft\Rebar\Auth\Reply $authReply */
					$authReply = $auth->GetAuthenticatedUser($this->request);
					$this->set('auth', $authReply);
				} catch (UserNotFoundException $e) {
					$this->response->Status = 404;
					$this->set('error', $e->getMessage());
				} catch (InvalidPasswordException $e) {
					$this->response->Status = 403;
					$this->set('error', $e->getMessage());
				} catch (\Exception $e) {
					$this->response->Status = 500;
					$this->set('error', $e->getMessage());
				}
				break;
			case 'POST':
				try {
					$body = json_decode($this->request->Body, true);

					if (!isset($body['credentials']) ||
						!isset($body['credentials']['username']) ||
						!isset($body['credentials']['password'])) {
						$this->response->Status = 400;
						$this->set(
							'error',
							'A credentials object is required to log in and must contain a username and password'
						);
					} else {
						$email    = $body['credentials']['username'];
						$password = $body['credentials']['password'];
						$remember = (isset($body['credentials']['remember']) ? $body['credentials']['remember'] : false);


						/** @var \Fluxoft\Rebar\Auth\Reply $authReply */
						$authReply = $auth->Login($email, $password, $remember);
						$this->set('auth', $authReply);
					}
				} catch (UserNotFoundException $e) {
					$this->response->Status = 404;
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
				$auth->Logout($this->request);
				$this->set('auth', false);
				break;
		}
	}

	protected function handleRepository(
		RepositoryInterface $repository,
		array $params
	) {
		// Force Json presenter for this type of controller (so all replies are in JSON format)
		// and set its Callback property from the value in $getVars['callback'], then unset that
		// value from the array if it exists.
		$this->presenterClass = 'Json';
		$this->presenter      = new Json();

		$callback = $this->request->Get('callback', false);
		if ($callback !== false) {
			$this->presenter->SetCallback($this->request->Get('callback', ''));
		}

		$reply = new Reply();

		switch (strtoupper($this->request->Method)) {
			case 'GET':
				$reply = $repository->Get($this->request, $params);
				break;
			case 'POST':
				$reply = $repository->Post($this->request, $params);
				break;
			case 'PUT':
				$reply = $repository->Put($this->request, $params);
				break;
			case 'PATCH':
				$reply = $repository->Patch($this->request, $params);
				break;
			case 'DELETE':
				$reply = $repository->Delete($this->request, $params);
				break;
			default:
				$reply->Status = 405;
				$reply->Error  = new Error(405, 'Unsupported method.');
		}

		if ($reply instanceof Reply) {
			$this->response->Status = $reply->Status;
			$this->set('meta', $reply->Meta);
			if (!empty($reply->Error)) {
				$this->set('error', $reply->Error);
			} else {
				$this->set('data', $reply->Data);
			}
		} else {
			$this->response->Status = 500;
			$this->set('error', new Error(500, 'Bad reply from repository'));
		}
	}
}
