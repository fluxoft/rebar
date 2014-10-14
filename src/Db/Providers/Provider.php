<?php
namespace Fluxoft\Rebar\Db\Providers;

use \Fluxoft\Rebar\Db\Exceptions\ProviderException;

abstract class Provider {
	protected $connection = null;

	public function __construct(\PDO $connection) {
		$this->connection = $connection;
	}

	public function __destruct() {
		unset($this->connection);
	}

	public function Insert($query, array $params = null, $sequence = null) {
		$newID = 0;
		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			$stmt = $this->connection->prepare($query);
			$stmt->execute($params);
			unset($stmt);
		} catch (\PDOException $e) {
			throw new ProviderException($e->getMessage());
		}
		if ($newID === 0) {
			$newID = $this->connection->lastInsertId($sequence);
		}
		return $newID;
	}

	public function Update($query, array $params = null) {
		$return = true;
		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			$stmt = $this->connection->prepare($query);
			$stmt->execute($params);
			unset($stmt);
		} catch (\PDOException $e) {
			throw new ProviderException($e->getMessage());
		}
		return $return;
	}

	public function Delete($query, array $params = null) {
		return $this->Update($query, $params);
	}

	public function SelectSet($query, array $params = null) {
		$return = array();
		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			$stmt = $this->connection->prepare($query);
			if ($stmt->execute($params)) {
				$return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			}
		} catch (\PDOException $e) {
			throw new ProviderException($e->getMessage());
		}
		return $return;
	}

	public function SelectValue($query, array $params = null) {
		$return = null;
		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		try {
			$stmt = $this->connection->prepare($query);
			if ($stmt->execute($params)) {
				while ($row = $stmt->fetch()) {
					$return = $row[0];
				}
			}
		} catch (\PDOException $e) {
			throw new ProviderException($e->getMessage());
		}
		return $return;
	}
}