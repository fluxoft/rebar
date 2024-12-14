<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class MariaDb
 * This class is a MariaDB database mapper, extending the GenericSql mapper.
 * It can be used to implement MariaDB-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
abstract class MariaDb extends GenericSql {
	
	/**
	 * Format an identifier for use in SQL specific to MariaDB.
	 * MariaDB uses backticks (`) to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteIdentifier(string $identifier): string {
		return "`$identifier`";
	}
}
