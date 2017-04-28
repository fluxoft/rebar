<?php

namespace Fluxoft\Rebar\Auth\Db;

use Doctrine\DBAL\Connection;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Db\Exceptions\ModelException;
use Fluxoft\Rebar\Db\Mapper;
use Fluxoft\Rebar\Db\MapperFactory;

class UserMapper extends Mapper implements UserMapperInterface {
	/** @var User */
	protected $model;

	/**
	 * @param $username
	 * @param $password
	 * @return User
	 * @throws InvalidPasswordException
	 * @throws UserNotFoundException
	 */
	public function GetAuthorizedUserForUsernameAndPassword($username, $password) {
		/** @var User $user */
		$user = $this->GetOneWhere([
			$this->model->GetAuthUsernameProperty() => $username
		]);
		if (isset($user)) {
			if ($user->IsPasswordValid($password)) {
				return $user;
			} else {
				throw new InvalidPasswordException(sprintf('Incorrect password'));
			}
		} else {
			throw new UserNotFoundException(sprintf('User not found'));
		}
	}

	/**
	 * Return the user for the given ID. Should be overridden if restrictions should be made on
	 * on how a user should be allowed access.
	 * @param $id
	 * @return mixed
	 */
	public function GetAuthorizedUserById($id) {
		return $this->GetOneById($id);
	}
}
