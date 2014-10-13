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
	 * Return a UserModel class for the user matching $username and $password.
	 * @param $username
	 * @param $password
	 * @return \Fluxoft\Rebar\Auth\UserModel
	 */
	public function GetByUsernameAndPassword($username, $password) {
		/** @var $userClass \Fluxoft\Rebar\Auth\UserModel */
		$userClass = new $this->namespacedModel($this);
		return $userClass->CheckLogin($username, $password);
	}

	public function GetByToken(Token $token) {
		return parent::GetOneById($token->UserID);
	}
} 