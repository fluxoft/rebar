<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class MySql
 * This class is a MySQL database mapper, extending the GenericSqlMapper.
 * It can be used to implement MySQL-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
abstract class MySqlMapper extends GenericSqlMapper {
	/**
	 * Format an identifier for use in SQL specific to MySQL.
	 * MySQL uses backticks (`) to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteElement(string $identifier): string {
		return "`$identifier`";
	}
}
