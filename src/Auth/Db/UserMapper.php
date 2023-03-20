<?php

namespace Fluxoft\Rebar\Auth\Db;

use Doctrine\DBAL\Exception;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Db\Mapper;
use Fluxoft\Rebar\Db\Model;

class UserMapper extends Mapper implements UserMapperInterface {
	/** @var User */
	protected Model $model;

	/**
	 * @param $username
	 * @param $password
	 * @return User
	 * @throws InvalidPasswordException|UserNotFoundException|Exception
	 */
	public function GetAuthorizedUserForUsernameAndPassword($username, $password): User {
		/** @var User $user */
		$user = $this->GetOneWhere([
			$this->model->GetAuthUsernameProperty() => $username
		]);
		if (isset($user)) {
			if ($user->IsPasswordValid($password)) {
				return $user;
			} else {
				throw new InvalidPasswordException('Incorrect password');
			}
		} else {
			throw new UserNotFoundException('User not found');
		}
	}

	/**
	 * Return the user for the given ID. Should be overridden if restrictions should be made on
	 * on how a user should be allowed access.
	 * @param $id
	 * @return Model|null
	 * @throws Exception
	 */
	public function GetAuthorizedUserById($id): ?Model {
		return $this->GetOneById($id);
	}
}
