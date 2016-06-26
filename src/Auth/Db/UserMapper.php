<?php

namespace Fluxoft\Rebar\Auth\Db;

use Doctrine\DBAL\Connection;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Db\Exceptions\ModelException;
use Fluxoft\Rebar\Db\Mapper;
use Fluxoft\Rebar\Db\MapperFactory;

abstract class UserMapper extends Mapper implements UserMapperInterface {
	/** @var User */
	protected $userModel;

	public function __construct(MapperFactory $mapperFactory, Connection $reader, Connection $writer = null) {
		parent::__construct($mapperFactory, $reader, $writer);

		if (!($this->model instanceof User)) {
			throw new ModelException(sprintf(
				'The model %s must be an instance of a class extended from Fluxoft\Rebar\Auth\User',
				$this->modelClass
			));
		}

		$this->userModel = $this->model;
	}

	/**
	 * @param $username
	 * @param $password
	 * @return User
	 * @throws InvalidPasswordException
	 * @throws UserNotFoundException
	 */
	public function GetOneForUsernameAndPassword($username, $password) {
		/** @var User $user */
		$user = $this->GetOneWhere(
			'{'.$this->userModel->GetAuthUsernameProperty().'} = :username',
			['username' => $username]
		);
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
}
