<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class MySql
 * This class is a MySQL database mapper, extending the GenericSql mapper.
 * It can be used to implement MySQL-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
class MySql extends GenericSql {
	/**
	 * Format an identifier for use in SQL specific to MySQL.
	 * MySQL uses backticks (`) to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
 protected function quoteIdentifier(string $identifier): string {
	 return "`$identifier`";
 }
}
