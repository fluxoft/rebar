<?php

namespace Fluxoft\Rebar\Data\Db;

/**
 * Class Join
 * This class represents a SQL JOIN clause, defining its type, table, and condition.
 * @package Fluxoft\Rebar\Db
 * @property string Type The type of join (e.g., INNER, LEFT).
 * @property string Table The table to join.
 * @property string On The ON clause for the join condition.
 */
final class Join {
	use \Fluxoft\Rebar\_Traits\GettableProperties;
	use \Fluxoft\Rebar\_Traits\SettableProperties;

	private $properties = [
		'Type' => '',
		'Table' => '',
		'On' => '',
		'Alias' => null
	];

	private const VALID_TYPES = ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS'];

	/**
	 * Constructor for a Join definition.
	 *
	 * @param string $type The type of join (e.g., "LEFT", "INNER").
	 * @param string $table The name of the table to join.
	 * @param string $on The ON clause for the join condition.
	 */
	public function __construct(string $type, string $table, string $on, ?string $alias = null) {
		$this->Type  = $type;
		$this->Table = $table;
		$this->On    = $on;
		$this->Alias = $alias;
	}

	protected function setType(mixed $type): void {
		if (is_string($type) && in_array(strtoupper($type), self::VALID_TYPES, true)) {
			$this->properties['Type'] = strtoupper($type);
		} else {
			throw new \InvalidArgumentException(sprintf(
				'Invalid JOIN type: %s. Valid types are: %s',
				$type,
				implode(', ', self::VALID_TYPES)
			));
		}
	}
	protected function getType(): string {
		return $this->properties['Type'];
	}

	protected function setTable(mixed $table): void {
		if (is_string($table) && !empty($table)) {
			$this->properties['Table'] = $table;
		} else {
			throw new \InvalidArgumentException('Table name must be a non-empty string.');
		}
	}
	protected function getTable(): string {
		return $this->properties['Table'];
	}

	protected function setOn(mixed $on): void {
		if (is_string($on) && !empty($on)) {
			$this->properties['On'] = $on;
		} else {
			throw new \InvalidArgumentException('ON clause must be a non-empty string.');
		}
	}
	protected function getOn(): string {
		return $this->properties['On'];
	}

	protected function setAlias(mixed $alias): void {
		if ($alias !== null && !is_string($alias)) {
			throw new \InvalidArgumentException('Alias must be a string or null.');
		}
		$this->properties['Alias'] = $alias;
	}
	protected function getAlias(): ?string {
		return $this->properties['Alias'] ?? null;
	}
}
