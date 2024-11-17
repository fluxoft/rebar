<?php

namespace Fluxoft\Rebar\Db\Mappers;

use PDO;

/**
 * Class SQLite
 * This class is a SQLite database mapper, extending the GenericSql mapper.
 * It can be used to implement SQLite-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Db\Mappers
 */
class SQLite extends GenericSql {
	/**
	 * Format an identifier for use in SQL specific to SQLite.
	 * SQLite uses double quotes (") to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
 protected function quoteIdentifier(string $identifier): string {
	 return "\"$identifier\"";
 }
}
