<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class SqlServer
 * This class is a SQL Server database mapper, extending the GenericSql mapper.
 * It can be used to implement SQL Server-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
class SqlServer extends GenericSql {
	/**
	 * Format an identifier for use in SQL specific to SQL Server.
	 * SQL Server uses square brackets ([]) to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteIdentifier(string $identifier): string {
		return "[$identifier]";
	}

	/**
	 * Used to apply pagination to a SQL statement specific to SQL Server.
	 * @param string $sql
	 * @param int $page
	 * @param int $pageSize
	 * @return string
	 */
	protected function applyPagination(string $sql, int $page, int $pageSize): string {
		if ($pageSize > 0) {
			$offset = ($page - 1) * $pageSize;
			if (stripos($sql, 'ORDER BY') === false) {
				$sql .= " ORDER BY (SELECT NULL)";
			}
			$sql .= " OFFSET $offset ROWS FETCH NEXT $pageSize ROWS ONLY";
		}
		return $sql;
	}
}
