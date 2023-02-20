<?php

namespace Fluxoft\Rebar\Auth\Db;

use PHPUnit\Framework\TestCase;

/**
 * Class TokenTest
 * @package Fluxoft\Rebar\Auth\Db
 */
class TokenTest extends TestCase {
	protected function setup():void {}

	protected function teardown():void {}

	public function testNullToken () {
		$this->expectException('Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException');

		// will throw exception (cannot create with all nulls)
		$token = new Token(null, null, null, null);
		unset($token);
		//echo (string) $token;
	}

	/**
	 * @param $userID
	 * @dataProvider userIdProvider
	 */
	public function testCreateTokenWithUserID($userID) {
		$token = new Token($userID);

		// make sure the token returns the $userID used to create it
		$this->assertEquals($userID, $token->UserID);
		// make sure the token returned a valid seriesID
		$this->assertEquals(true, $this->validateSeriesId($token->SeriesID));
		// make sure the token returned a valid token string
		$this->assertEquals(true, $this->validateToken($token->Token));
		// make sure the token when cast to string is valid
		$this->assertEquals(true, $this->validateTokenString((string) $token));
	}
	public function userIdProvider() {
		return [
			[1],
			[2],
			[3],
			[4],
			[5]
		];
	}

	/**
	 * @param $userID
	 * @param $seriesID
	 * @dataProvider userIdSeriesIdProvider
	 */
	public function testCreateTokenWithUserIdAndSeriesId($userID, $seriesID) {
		$token = new Token ($userID, $seriesID);

		// make sure the token returns the $userID used to create it
		$this->assertEquals($userID, $token->UserID);
		// make sure the token return the $seriesID used to create it
		$this->assertEquals($seriesID, $token->SeriesID);
		// make sure the token returned a valid token string
		$this->assertEquals(true, $this->validateToken($token->Token));
		// make sure the token when cast to string is valid
		$this->assertEquals(true, $this->validateTokenString((string) $token));
	}
	public function userIdSeriesIdProvider() {
		return [
			[1, '54571765e346c1.60640599'],
			[2, '545714083383e1.26100867'],
			[3, '545714083383e1.26100867'],
			[4, '545714083383e1.26100867'],
			[5, '545714083383e1.26100867']
		];
	}

	/**
	 * @param $userID
	 * @param $seriesID
	 * @param $tokenValue
	 * @dataProvider userIdSeriesIdTokenProvider
	 */
	public function testCreateTokenWithUserIdAndSeriesIdAndToken($userID, $seriesID, $tokenValue) {
		$token = new Token ($userID, $seriesID, $tokenValue);

		// make sure the token returns the $userID used to create it
		$this->assertEquals($userID, $token->UserID);
		// make sure the token returns the $seriesID used to create it
		$this->assertEquals($seriesID, $token->SeriesID);
		// make sure the token returns the $token used to create it
		$this->assertEquals($tokenValue, $token->Token);
		// make sure the token when cast to string is valid
		$this->assertEquals(true, $this->validateTokenString((string) $token));
	}
	public function userIdSeriesIdTokenProvider() {
		return [
			[1, '54571765e346c1.60640599', '{3B99B6DD-892A-4135-3AC1-498115339856}'],
			[2, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'],
			[3, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'],
			[4, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'],
			[5, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}']
		];
	}

	/**
	 * @param $tokenString
	 * @dataProvider tokenStringProvider
	 */
	public function testCreateTokenFromTokenString($tokenString) {
		$token = new Token(null, null, null, $tokenString);

		list($userID, $seriesID, $tokenValue) = explode('|', base64_decode($tokenString));

		// make sure the token's UserID was set to the value encoded in the token string
		$this->assertEquals($userID, $token->UserID);
		// make sure the token's UserID was set to the value encoded in the token string
		$this->assertEquals($seriesID, $token->SeriesID);
		// make sure the token's UserID was set to the value encoded in the token string
		$this->assertEquals($tokenValue, $token->Token);
	}
	public function tokenStringProvider() {
		return [
			[base64_encode(
				implode('|', [1, '54571765e346c1.60640599', '{3B99B6DD-892A-4135-3AC1-498115339856}'])
			)],
			[base64_encode(
				implode('|', [2, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'])
			)],
			[base64_encode(
				implode('|', [3, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'])
			)],
			[base64_encode(
				implode('|', [4, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'])
			)],
			[base64_encode(
				implode('|', [5, '545714083383e1.26100867', '{3B99B6DD-892A-4135-3AC1-498115339856}'])
			)]
		];
	}

	/**
	 * @dataProvider invalidTokenProvider
	 */
	public function testInvalidToken($invalidToken) {
		$this->expectException('Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException');
		$token = new Token(null, null, null, $invalidToken);
	}
	public function invalidTokenProvider() {
		return [
			[base64_encode(
				implode('|', [1])
			)],
			[base64_encode(
				implode('|', [2, '545714083383e1.26100867'])
			)]
		];
	}

	private function validateSeriesId($seriesID) {
		// seriesID should be the result of uniqid(), which returns a 23-digit string
		$pattern = '/[a-z0-9\.]{23}/';
		return (preg_match($pattern, $seriesID) === 1);
	}

	private function validateToken($token) {
		// a token is a GUID
		$pattern = '/{[0-9A-Z]{8}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{12}}/';
		return (preg_match($pattern, $token) === 1);
	}

	private function validateTokenString($tokenString) {
		list($userID, $seriesID, $token) = explode('|', base64_decode($tokenString));
		return (
			isset($userID) &&
			$this->validateSeriesId($seriesID) &&
			$this->validateToken($token)
		);
	}
}
