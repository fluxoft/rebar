<?php

namespace Fluxoft\Rebar\Db\Mappers;

use PDO;

/**
* Class Oracle
* This class is an Oracle database mapper, extending the GenericSql mapper.
* It can be used to implement Oracle-specific logic as needed.
*
* @package Fluxoft\Rebar\Db\Mappers
*/
class Oracle extends GenericSql {
	/**
	* Format an identifier for use in SQL specific to Oracle.
	* Oracle uses double quotes (") to quote identifiers.
	*
	* @param string $identifier
	* @return string
	*/
	protected function quoteIdentifier(string $identifier): string {
		return "\"$identifier\"";
	}
	
	/**
	* Format values for insert specific to Oracle.
	* @param string $type
	* @param mixed $value
	* @return mixed
	*/
	protected function formatValueForInsert(string $type, mixed $value): mixed {
		if (is_string($value)) {
			// Parse strings into DateTime objects
			$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: 
			\DateTime::createFromFormat('Y-m-d', $value) ?: 
			\DateTime::createFromFormat('H:i:s', $value) ?: 
			\DateTime::createFromFormat('h:i A', $value);
			
			if ($dateTime) {
				// Format to the correct string based on type
				switch ($type) {
					case 'datetime':
						return $dateTime->format('Y-m-d H:i:s');
					case 'date':
						return $dateTime->format('Y-m-d');
					case 'time':
						return $dateTime->format('H:i:s');
				}
			} else {
				throw new \InvalidArgumentException("Invalid date/time format for value '$value'");
			}
		}

		// If already a DateTime object, use the base class logic
		return parent::formatValueForInsert($type, $value);
	}
				
	/**
	* Used to apply pagination to a SQL statement specific to Oracle.
	* @param string $sql
	* @param int $page
	* @param int $pageSize
	* @return string
	*/
	protected function applyPagination(string $sql, int $page, int $pageSize): string {
		if ($pageSize > 0) {
			$offset = ($page - 1) * $pageSize;
			$sql    = "SELECT * FROM (SELECT a.*, ROWNUM rnum
			FROM ($sql) a WHERE ROWNUM <= " . ($offset + $pageSize) . ") WHERE rnum > $offset";
		}
		return $sql;
	}
}
