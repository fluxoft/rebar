<?php

namespace Fluxoft\Rebar\Auth\Db;

use Doctrine\DBAL\Connection;

class TokenMapper {
	/** @var Connection */
	protected $reader;
	/** @var Connection */
	protected $writer;

	public function __construct(
		Connection $reader,
		Connection $writer = null
	) {
		$this->reader = $reader;
		$this->writer = (isset($writer)) ? $writer : $reader;
	}

	public function CheckAuthToken (Token $token) {
		// first, delete any expired tokens so they can't be returned
		$sql = 'DELETE FROM auth_tokens WHERE expires_on < NOW()';
		$this->writer->executeQuery($sql);

		// now try to find a matching auth_token
		$sql    = <<<EOF
SELECT user_id, series_id, token
FROM auth_tokens
WHERE user_id = :userID AND
	series_id = :seriesID AND
	token = :token AND
	expires_on > NOW()
EOF;
		$params = [
			'userID' => $token->UserID,
			'seriesID' => $token->SeriesID,
			'token' => $token->Token
		];
		$types  = [
			'integer',
			'string',
			'string'
		];
		$stmt   = $this->writer->executeQuery($sql, $params, $types);
		$rows   = $stmt->fetchAll();

		return (!empty($rows));
	}
	public function SaveAuthToken (Token $token) {
		$sql    = <<<EOF
REPLACE INTO auth_tokens (user_id, series_id, token, expires_on)
VALUES (:user_id, :series_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY)
EOF;
		$params = [
			'user_id' => $token->UserID,
			'series_id' => $token->SeriesID,
			'token' => $token->Token
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
