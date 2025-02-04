<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class Postgres
 * This class is a PostgreSQL database mapper, extending the GenericSqlMapper.
 * It can be used to implement PostgreSQL-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
abstract class PostgresMapper extends GenericSqlMapper {
	/**
	 * Format an identifier for use in SQL specific to PostgreSQL.
	 * PostgreSQL uses double quotes (") to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteElement(string $identifier): string {
		return "\"$identifier\"";
	}

	/**
	 * Format values for insert specific to PostgreSQL.
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	protected function formatValueForInsert(string $type, mixed $value): mixed {
		// Handle DateTime objects directly
		if ($value instanceof \DateTime) {
			switch ($type) {
				case 'datetime': // @codeCoverageIgnore
					return $value->format('Y-m-d H:i:s');
				case 'date': // @codeCoverageIgnore
					return $value->format('Y-m-d');
				case 'time': // @codeCoverageIgnore
					return $value->format('H:i:s');
				default:
					// If the type is not a date/time type, use the base class logic
					return parent::formatValueForInsert($type, $value);
			}
		}

		// Attempt to parse strings into DateTime objects and reformat
		if (in_array($type, ['datetime', 'date', 'time']) && is_string($value)) {
			$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $value) ?:
						\DateTime::createFromFormat('Y-m-d', $value) ?:
						\DateTime::createFromFormat('H:i:s', $value) ?:
						\DateTime::createFromFormat('h:i A', $value);

			if ($dateTime) {
				// Format to the correct string based on type
				switch ($type) {
					case 'datetime': // @codeCoverageIgnore
						return $dateTime->format('Y-m-d H:i:s');
					case 'date': // @codeCoverageIgnore
						return $dateTime->format('Y-m-d');
					case 'time': // @codeCoverageIgnore
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
