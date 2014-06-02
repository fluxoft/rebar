<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 5/26/14
 * Time: 10:18 AM
 */

namespace Fluxoft\Rebar\Auth;

/**
 * Class Token
 * @package Fluxoft\Rebar\Auth
 * @property int UserID
 * @property string SeriesID
 * @property string Token
 */
class Token {
	protected $userID;
	protected $seriesID;
	protected $token;

	public function __construct(
		$userID,
		$seriesID = '',
		$token = '',
		$tokenString = ''
	) {
		if ($tokenString) {
			// split the tokenString on the pipe
			$tokenBits = explode('|', base64_decode($tokenString));
			// token should be in 3 parts
			if (count($tokenBits) !== 3) {
				throw new Exceptions\InvalidTokenException('Invalid Token String "%s"', $tokenString);
			} else {
				list($userID, $seriesID, $token) = $tokenBits;
				$this->userID = $userID;
				$this->seriesID = $seriesID;
				$this->token = $token;
			}
		} else {
			$this->userID = $userID;
			if ($seriesID) {
				$this->seriesID = $seriesID;
			} else {
				$this->seriesID = uniqid();
			}
			if ($token) {
				$this->token = $token;
			} else {
				$this->token = $this->getGUID();
			}
		}
	}

	public function __get($var) {
		$val = '';
		switch ($var) {
			case 'UserID':
				$val = $this->userID;
				break;
			case 'SeriesID':
				$val = $this->seriesID;
				break;
			case 'Token':
				$val = $this->token;
				break;
			default:
				throw new \Exception('Property not found.');
				break;
		}
		return $val;
	}



	public function __toString() {
		return base64_encode(
			implode('|', array(
				$this->userID,
				$this->seriesID,
				$this->token
			))
		);
	}

	private function getGUID() {
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
				.substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12)
				.chr(125);// "}"
			return $uuid;
		}
	}
} 