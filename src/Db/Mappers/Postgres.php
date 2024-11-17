<?php

namespace Fluxoft\Rebar\Db\Mappers;

use PDO;

/**
 * Class Postgres
 * This class is a PostgreSQL database mapper, extending the GenericSql mapper.
 * It can be used to implement PostgreSQL-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Db\Mappers
 */
class Postgres extends GenericSql {
	/**
	 * Format an identifier for use in SQL specific to PostgreSQL.
	 * PostgreSQL uses double quotes (") to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteIdentifier(string $identifier): string {
		return "\"$identifier\"";
	}

	/**
	 * Format values for insert specific to PostgreSQL.
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	protected function formatValueForInsert(string $type, mixed $value): mixed {
		if (is_string($value)) {
			// Attempt to parse strings into DateTime objects and reformat
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
}