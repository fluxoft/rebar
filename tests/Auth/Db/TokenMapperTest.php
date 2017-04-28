<?php

namespace Auth\Db;

use Fluxoft\Rebar\Auth\Db\Token;
use Fluxoft\Rebar\Auth\Db\TokenMapper;
use PHPUnit\Framework\TestCase;

class TokenMapperTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $connectionObserver;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $statementObserver;

	public function setup() {
		$this->connectionObserver = $this->getMockBuilder('\Doctrine\DBAL\Connection')
			->disableOriginalConstructor()
			->getMock();
		$this->statementObserver  = $this->getMockBuilder('\Doctrine\DBAL\Statement')
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown() {
		unset($this->statementObserver);
		unset($this->connectionObserver);
	}

	/**
	 * @param $foundRows
	 * @param $expectedReturn
	 * @dataProvider checkAuthTokenProvider
	 */
	public function testCheckAuthToken($foundRows, $expectedReturn) {
		$tokenMapper = new TokenMapper($this->connectionObserver);
		$token       = new Token(1, 2, 'token');

		$deleteSql = 'DELETE FROM auth_tokens WHERE expires_on < NOW()';
		$searchSql = <<<EOF
SELECT user_id, series_id, token
FROM auth_tokens
WHERE user_id = :userID AND
	series_id = :seriesID AND
	token = :token AND
	expires_on > NOW()
EOF;


		$this->connectionObserver
			->expects($this->at(0))
			->method('executeQuery')
			->with($deleteSql)
			->will($this->returnValue(null));

		$this->connectionObserver
			->expects($this->at(1))
			->method('executeQuery')
			->with(
				$searchSql,
				[
					'userID' => $token->UserID,
					'seriesID' => $token->SeriesID,
					'token' => $token->Token
				],
				[
					'integer',
					'string',
					'string'
				]
			)
			->will($this->returnValue($this->statementObserver));

		$this->statementObserver
			->expects($this->once())
			->method('fetchAll')
			->will($this->returnValue($foundRows));

		$checked = $tokenMapper->CheckAuthToken($token);

		$this->assertEquals($expectedReturn, $checked);
	}
	public function checkAuthTokenProvider() {
		return [
			'found' => [
				'foundRows' => [],
				'expectedReturn' => false
			],
			'notfound' => [
				'foundRows' => ['found'],
				'expectedReturn' => true
			]
		];
	}
	public function testSaveAuthToken() {
		$tokenMapper = new TokenMapper($this->connectionObserver);
		$token       = new Token(1, 2, 'token');

		$sql    = <<<EOF
REPLACE INTO auth_tokens (user_id, series_id, token, expires_on)
VALUES (:user_id, :series_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY)
EOF;
		$params = [
			'user_id' => $token->UserID,
			'series_id' => $token->SeriesID,
			'token' => $token->Token
		];

		$this->connectionObserver
			->expects($this->once())
			->method('executeQuery')
			->with($sql, $params);

		$tokenMapper->SaveAuthToken($token);
	}

	/**
	 * @param $userId
	 * @param null $seriesId
	 * @param null $token
	 * @dataProvider deleteAuthTokenProvider
	 */
	public function testDeleteAuthToken($userId, $seriesId = null, $token = null) {
		$tokenMapper = new TokenMapper($this->connectionObserver);

		$sql    = 'DELETE FROM auth_tokens WHERE user_id = :user_id';
		$params = ['user_id' => $userId];
		if (isset($seriesId)) {
			$sql                .= ' AND series_id = :series_id';
			$params['series_id'] = $seriesId;
		}

		if (isset($token)) {
			$sql            .= ' AND token = :token';
			$params['token'] = $token;
		}

		$this->connectionObserver
			->expects($this->once())
			->method('executeQuery')
			->with($sql, $params);

		$tokenMapper->DeleteAuthToken($userId, $seriesId, $token);
	}
	public function deleteAuthTokenProvider() {
		return [
			[
				'userId' => 1,
				'seriesId' => null,
				'token' => null
			],
			[
				'userId' => 1,
				'seriesId' => 'series',
				'token' => null
			],
			[
				'userId' => 1,
				'seriesId' => null,
				'token' => 'token'
			],
			[
				'userId' => 1,
				'seriesId' => 'series',
				'token' => 'token'
			]
		];
	}
}
