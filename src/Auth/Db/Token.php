<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use Fluxoft\Rebar\Model;

/**
 * Class Token
 * @package Fluxoft\Rebar\Auth\Web
 * @property int UserID
 * @property string SeriesID
 * @property string Token
 */
class Token extends Model {
	protected $properties = [
		'UserID' => 0,
		'SeriesID' => 0,
		'Token' => ''
	];

	public function __construct(
		$userID      = null,
		$seriesID    = null,
		$token       = null,
		$tokenString = null
	) {
		if (isset($tokenString)) {
			// split the tokenString on the pipe
			$tokenBits = explode('|', base64_decode($tokenString));
			// token should be in 3 parts
			if (count($tokenBits) !== 3) {
				throw new InvalidTokenException('Invalid Token String "%s"', $tokenString);
			} else {
				list($userID, $seriesID, $token) = $tokenBits;

				$this->UserID   = $userID;
				$this->SeriesID = $seriesID;
				$this->Token    = $token;
			}
		} elseif (isset($userID)) {
			$this->UserID   = $userID;
			$this->SeriesID = (isset($seriesID)) ? $seriesID : uniqid('', true);
			$this->Token    = (isset($token)) ? $token : $this->getGUID();
		} else {
			throw new InvalidTokenException(sprintf(
				'You must provide, at minimum, either the $userID or a $tokenString parameter to create a Token.'
			));
		}
	}

	public function __toString() {
		return base64_encode(
			implode('|', [
				$this->UserID,
				$this->SeriesID,
				$this->Token
			])
		);
	}

	private function getGUID() {
		if (function_exists('com_create_guid')) {
			return com_create_guid();
		} else {
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45); // "-"
			$uuid   = chr(123) // "{"
				.substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid, 12, 4).$hyphen
				.substr($charid, 16, 4).$hyphen
				.substr($charid, 20, 12)
				.chr(125); // "}"
			return $uuid;
		}
	}
}
