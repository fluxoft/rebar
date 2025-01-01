<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class SQLite
 * This class is a SQLite database mapper, extending the GenericSqlMapper.
 * It can be used to implement SQLite-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
abstract class SQLiteMapper extends GenericSqlMapper {
	/**
	 * Format an identifier for use in SQL specific to SQLite.
	 * SQLite uses double quotes (") to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteElement(string $identifier): string {
		return "\"$identifier\"";
	}
}
