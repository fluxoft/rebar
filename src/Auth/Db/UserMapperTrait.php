<?php

namespace Fluxoft\Rebar\Auth\Db;

use Doctrine\DBAL\Exception;
use Fluxoft\Rebar\Auth\Exceptions\InvalidCredentialsException;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Data\Db\Filter;
use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;

/**
 * Trait UserMapperTrait
 * Provides implementation for the UserMapperInterface methods.
 * 
 * This trait assumes that the implementing class:
 * - Implements the MapperInterface (which would be true when importing any of the db-specific mappers)
 * - Implements the UserMapperInterface
 * - Defines the $model property as an instance of User
 * 
 * @mixin \Fluxoft\Rebar\Data\Db\Mappers\MapperInterface
 * @mixin \Fluxoft\Rebar\Auth\Db\UserMapperInterface
 * 
 * @property-read User $model
 */ 
trait UserMapperTrait {
	/**
	 * Retrieve the user for the given username and password.
	 *
	 * @param string $username
	 * @param string $password
	 * @return UserInterface
	 * @throws InvalidPasswordException|UserNotFoundException|Exception
	 */
	public function GetAuthorizedUserForUsernameAndPassword(string $username, string $password): UserInterface {
		/** @var MapperInterface|UserMapperTrait $this */
		$this->enforceMapperRequirements();

		/** @var User $user */
		$user = $this->GetOne([
			new Filter($this->model->GetAuthUsernameProperty(), '=', $username)
		]);
		if (isset($user)) {
			if ($user->IsPasswordValid($password)) {
				return $user;
			} else {
				throw new InvalidCredentialsException('Incorrect password');
			}
		} else {
			throw new UserNotFoundException('User not found');
		}
	}

	/**
	 * Return the user for the given ID. Should be overridden if restrictions should be made on
	 * how a user should be allowed access.
	 *
	 * @param mixed $id
	 * @return UserInterface
	 * @throws Exception
	 */
	public function GetAuthorizedUserById(mixed $id): UserInterface {
		/** @var MapperInterface|UserMapperTrait $this */
		$this->enforceMapperRequirements();

		/** @var User $user */
		$user = $this->GetOneById($id);
		if (isset($user)) {
			return $user;
		} else {
			throw new UserNotFoundException('User not found');
		}
	}

	/**
	 * Ensure the class using this trait implements the required interface.
	 *
	 * @return void
	 * @throws \LogicException
	 */
	protected function enforceMapperRequirements(): void {
		// Ensure the class using this trait implements the UserMapperInterface
		if (!($this instanceof UserMapperInterface)) {
			throw new \LogicException(sprintf(
				'The class %s must implement %s to use %s.',
				static::class,
				UserMapperInterface::class,
				__TRAIT__
			));
		}
		// Ensure the class using this trait implements the MapperInterface
		if (!($this instanceof MapperInterface)) {
			throw new \LogicException(sprintf(
				'The class %s must implement %s to use %s.',
				static::class,
				MapperInterface::class,
				__TRAIT__
			));
		}
		// Ensure the class's model implements the UserInterface
		if (!($this->model instanceof UserInterface)) {
			throw new \LogicException(sprintf(
				'The class %s must implement %s to use %s.',
				static::class,
				UserInterface::class,
				__TRAIT__
			));
		}
	}
}
