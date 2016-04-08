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

	public function GetOneForUsernameAndPassword($username, $password) {
		/** @var User $user */
		$user   = $this->GetOneWhere(
			'{'.$this->userModel->GetAuthUsernameProperty().'} = :username',
			['username' => $username]
		);
		$return = null;
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

	public function CheckAuthToken (Token $token) {
		// first, delete any expired tokens so they can't be returned
		$now    = new \DateTime('now', new \DateTimeZone('UTC'));
		$now    = $now->format('Y-m-d H:i:s');
		$sql    = 'DELETE FROM auth_tokens WHERE expires_on < :now';
		$params = ['now' => $now];
		$this->writer->executeQuery($sql, $params, ['datetime']);

		// now try to find a matching auth_token
		$sql    = 'SELECT user_id, series_id, token FROM auth_tokens WHERE
		user_id = :userID AND series_id = :seriesID AND token = :token
		AND expires_on > :now';
		$params = [
			'userID' => $token->UserID,
			'seriesID' => $token->SeriesID,
			'token' => $token->Token,
			'now' => $now
		];
		$types  = [
			'integer',
			'string',
			'string',
			'datetime'
		];
		$stmt   = $this->writer->executeQuery($sql, $params, $types);
		$rows   = $stmt->fetchAll();

		return (!empty($rows));
	}
	public function SaveAuthToken (Token $token) {
		$sql     = 'INSERT INTO auth_tokens (user_id, series_id, token, expires_on)
		            VALUES (:user_id, :series_id, :token, :expires_on)
		            ON DUPLICATE KEY UPDATE token = :token2, expires_on = :expires_on2';
		$expires = new \DateTime('+30 days', new \DateTimeZone('UTC'));
		$params  = [
			'user_id' => $token->UserID,
			'series_id' => $token->SeriesID,
			'token' => $token->Token,
			'token2' => $token->Token,
			'expires_on' => $expires->format('Y-m-d H:i:s'),
			'expires_on2' => $expires->format('Y-m-d H:i:s')
		];
		$this->writer->executeQuery($sql, $params);
	}
	public function DeleteAuthToken ($userID, $seriesID = null, $token = null) {
		$sql    = 'DELETE FROM auth_tokens WHERE user_id = :user_id';
		$params = ['user_id' => $userID];

		if (isset($seriesID)) {
			$sql                .= ' AND series_id = :series_id';
			$params['series_id'] = $seriesID;
		}

		if (isset($token)) {
			$sql            .= ' AND token = :token';
			$params['token'] = $token;
		}
		$this->writer->executeQuery($sql, $params);
	}
}
