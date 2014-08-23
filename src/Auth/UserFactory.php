<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 5/26/14
 * Time: 1:22 AM
 */

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Db\ModelFactory;
use Fluxoft\Rebar\Db\Providers\Provider;

class UserFactory extends ModelFactory {
	/**
	 * The name of the UserModel class.
	 * @var string $userModel
	 */
	protected $userModel;

	public function __construct(
		$userModel,
		Provider $reader,
		Provider $writer,
		$modelNamespace = ''
	) {
		$this->userModel = $modelNamespace.$userModel;
		parent::__construct($reader, $writer);
	}

	/**
	 * Return a UserModel class for the user matching $username and $password.
	 * @param $username
	 * @param $password
	 * @return \Fluxoft\Rebar\Auth\UserModel
	 */
	public function GetAuthenticatedUser($username, $password) {
		/** @var $userClass \Fluxoft\Rebar\Auth\UserModel */
		$userClass = new $this->userModel($this->reader, $this->writer);
		return $userClass->CheckLogin($username, $password);
	}

	public function GetByToken(Token $token) {
		return $this->GetOneById($this->userModel, $token->UserID);
	}
} 