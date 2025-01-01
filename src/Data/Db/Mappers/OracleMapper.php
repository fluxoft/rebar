<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use PDO;

/**
 * Class Oracle
 * This class is an Oracle database mapper, extending the GenericSqlMapper.
 * It can be used to implement Oracle-specific logic as needed.
 *
 * @package Fluxoft\Rebar\Data\Db\Mappers
 */
abstract class OracleMapper extends GenericSqlMapper {
	/**
	 * Format an identifier for use in SQL specific to Oracle.
	 * Oracle uses double quotes (") to quote identifiers.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function quoteElement(string $identifier): string {
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
