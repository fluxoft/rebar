<?php

namespace Fluxoft\Rebar\Db;

use Fluxoft\Rebar\Db\Exceptions\FactoryException;
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
	/**
	 * array(
	 *   'FactoryName' => '\\Namespace\\FactoryClass''
	 * )
	 * @var array $factoryClasses
	 */
	protected $factoryClasses;

	public function __construct(
		Provider $reader,
		Provider $writer,
		$namespace = '',
		$factoryClasses = array()
	) {
		$this->reader         = $reader;
		$this->writer         = $writer;
		$this->namespace      = $namespace;
		$this->factoryClasses = $factoryClasses;
	}

	private $factories = array();
	public function Build($factory) {
		if (!isset($this->factories[$factory])) {
			if (isset($this->factoryClasses[$factory])) {
				if (!class_exists($this->factoryClasses[$factory])) {
					throw new FactoryException(sprintf(
						'User-defined model factory %s cannot be found.',
						$this->factoryClasses[$factory]
					));
				} else {
					$this->factories[$factory] = new $this->factoryClasses[$factory](
						$this->reader,
						$this->writer,
						$factory,
						$this->namespace
					);
				}
			} else{
				$this->factories[$factory] = new ModelFactory(
					$this->reader,
					$this->writer,
					$factory,
					$this->namespace
				);
			}
		}
		return $this->factories[$factory];
	}
} 