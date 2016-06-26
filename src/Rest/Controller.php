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
			case 'POST':
				$body = json_decode($this->request->Body, true);

				$email    = $body['credentials']['username'];
				$password = $body['credentials']['password'];
				$remember = (isset($body['credentials']['remember']) ? $body['credentials']['remember'] : false);
				try {
					/** @var \Fluxoft\Rebar\Auth\Reply $authReply */
					$authReply = $auth->Login($email, $password, $remember);
					$this->set('auth', $authReply);
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
			case 'DELETE':
				$reply = $repository->Delete($this->request, $params);
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
