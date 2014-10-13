<?php

namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Db\Providers\Provider;

class FactoryBuilder {
	/**
	 * The provider used to read from the database.
	 * @var \Fluxoft\Rebar\Db\Providers\Provider
	 */
	protected $reader = null;
	/**
	 * The provider used to write to the database.
	 * @var \Fluxoft\Rebar\Db\Providers\Provider
	 */
	protected $writer = null;
	/**
	 * @var string $namespace
	 */
	protected $namespace;

	public function __construct(
		Provider $reader,
		Provider $writer,
		$namespace = ''
	) {
		$this->reader = $reader;
		$this->writer = $writer;
		$this->namespace = $namespace;
	}

	private $factories = array();
	public function GetFactory($factory) {
		if (!isset($this->factories[$factory])) {
			$this->factories[$factory] = new ModelFactory(
				$this->reader,
				$this->writer,
				$factory,
				$this->namespace
			);
		}
		return $this->factories[$factory];
	}
} 