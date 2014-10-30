<?php
namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\Exceptions\UserNotFoundException;
use Fluxoft\Rebar\Auth\Exceptions\InvalidPasswordException;
use Fluxoft\Rebar\Db\Model;

abstract class UserModel extends Model {
	protected $authUsernameProperty = 'Email';
	protected $authPasswordProperty = 'Password';

	public function GetID () {
		return $this->properties[$this->idProperty];
	}

	public function CheckLogin ($username, $password) {
		$query  = 'SELECT '.
			$this->propertyDbSelectMap[$this->idProperty] . ', ' .
			$this->propertyDbSelectMap[$this->authPasswordProperty] .
			' FROM ' . $this->dbSelectTable .
			' WHERE '.
			$this->propertyDbSelectMap[$this->authUsernameProperty].' = :username';
		$params = array(
			'username' => $username
		);
		$rows   = $this->reader->SelectSet($query, $params);
		if (empty($rows)) {
			throw new UserNotFoundException(sprintf('Username %s not found.', $username));
		} else {
			$row = $rows[0];
			if ($this->validatePassword($password, $row[$this->propertyDbSelectMap[$this->authPasswordProperty]])) {
				$userClass = get_class($this);
				$return    = new $userClass($this->factory, $row[$this->propertyDbMap[$this->idProperty]]);
			} else {
				throw new InvalidPasswordException('Incorrect password.');
			}
		}
		return $return;
	}

	public function IsTokenValid (Token $token) {
		// try to retrieve a non-expired match from login_tokens
		$query  = 'SELECT COUNT(user_id) FROM auth_tokens WHERE
						user_id = :user_id AND series_id = :series_id AND token = :token AND
						expires_on > CURRENT_DATE';
		$params = array(
			':user_id' => $token->UserID,
			':series_id' => $token->SeriesID,
			':token' => $token->Token
		);
		return ($this->reader->SelectValue($query, $params) > 0);
	}

	public function GetCurrentAuthToken ($seriesID) {
		$query  = 'SELECT token FROM auth_tokens WHERE
						user_id = :user_id AND series_id = :series_id AND
						expires_on > CURRENT_DATE';
		$params = array(
			'user_id' => $this->properties[$this->idProperty],
			'series_id' => $seriesID
		);
		return $this->reader->SelectValue($query, $params);
	}

	public function SaveAuthToken($seriesID = '') {
		// get a new Token for this User
		$token   = new Token($this->properties[$this->idProperty], $seriesID);
		$expires = new \DateTime();
		$expires = $expires->add(new \DateInterval('P10D'))->format('Y-m-d H:i:s');

		// generate a new series ID, if none was provided
		if (strlen($seriesID)) {
			// update the series ID
			$query = 'UPDATE auth_tokens SET token = :token, expires_on = :expires_on
					WHERE user_id = :user_id AND series_id = :series_id';
		} else {
			// add a new database entry
			$query = 'INSERT INTO auth_tokens
				(user_id, series_id, token, expires_on)
				VALUES
				(:user_id, :series_id, :token, :expires_on)';
		}
		$params = array(
			'user_id' => $token->UserID,
			'series_id' => $token->SeriesID,
			'token' => $token->Token,
			'expires_on' => $expires
		);
		if ($this->writer->Update($query, $params)) {
			return $token;
		} else {
			return null;
		}
	}

	public function CheckAuthToken(Token $token) {
		// try to retrieve a non-expired match from auth_tokens
		$query  = 'SELECT COUNT(user_id) FROM auth_tokens WHERE
						user_id = :user_id AND series_id = :series_id AND token = :token AND
						expires_on > CURRENT_DATE';
		$params = array(
			'user_id' => $token->UserID,
			'series_id' => $token->SeriesID,
			'token' => $token->Token
		);
		$found  = $this->reader->SelectValue($query, $params);
		return ($found > 0);
	}

	public function DeleteAuthTokens($seriesID = '') {
		$query = 'DELETE FROM auth_tokens WHERE user_id = :user_id';
		if ($seriesID == '') {
			$params = array(
				':user_id' => $this->properties[$this->idProperty]
			);
		} else {
			$query .= ' AND series_id = :series_id';
			$params = array(
				':user_id' => $this->properties[$this->idProperty],
				':series_id' => $seriesID
			);
		}
		$this->writer->Delete($query, $params);
	}

	protected function setPassword ($password) {
		$hash                                             = $this->generateHash($password);
		$this->modProperties[$this->authPasswordProperty] = $hash;
	}
	protected function getPassword () {
		// do not return the actual password as a property.
		return "********";
	}

	private function generateHash ($password, $cost = 11) {
		/* To generate the salt, first generate enough random bytes. Because
		 * base64 returns one character for each 6 bits, the we should generate
		* at least 22*6/8=16.5 bytes, so we generate 17. Then we get the first
		* 22 base64 characters
		*/
		$salt = substr(base64_encode(openssl_random_pseudo_bytes(17)), 0, 22);
		/* As blowfish takes a salt with the alphabet ./A-Za-z0-9 we have to
		 * replace any '+' in the base64 string with '.'. We don't have to do
			* anything about the '=', as this only occurs when the b64 string is
		* padded, which is always after the first 22 characters.
		*/
		$salt = str_replace("+", ".", $salt);
		/* Next, create a string that will be passed to crypt, containing all
		 * of the settings, separated by dollar signs
		*/
		$param = '$' . implode('$', array(
				"2y",                              //select the most secure version of blowfish (>=PHP 5.3.7)
				str_pad($cost, 2, "0", STR_PAD_LEFT), //add the cost in two digits
				$salt                              //add the salt
			));

		//now do the actual hashing
		return crypt($password, $param);
	}

	/*
	 * Check the password against a hash generated by the generate_hash
	* function.
	*/
	private function validatePassword ($password, $hash) {
		/* Regenerating the hash with an available hash as the options parameter should
		 * produce the same hash if the same password is passed.
		*/
		return crypt($password, $hash) === $hash;
	}
}