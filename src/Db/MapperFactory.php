<?php

namespace Fluxoft\Rebar\Db;

use Doctrine\DBAL\Connection;
use Fluxoft\Rebar\Db\Exceptions\MapperFactoryException;
use Fluxoft\Rebar\FactoryInterface;

/**
 * Class MapperFactory
 * @package Fluxoft\Rebar\Db
 */
abstract class MapperFactory implements FactoryInterface {
	/** @var Connection */
	protected $reader;
	/** @var Connection */
	protected $writer;
	/** @var string */
	protected $mapperNamespace = '';

	/**
	 * @param Connection $reader
	 * @param Connection $writer
	 */
	public function __construct(Connection $reader, Connection $writer = null) {
		$this->reader = $reader;
		$this->writer = (isset($writer)) ? $writer : $reader;
	}

	/**
	 * @param $className
	 * @return Mapper
	 * @throws MapperFactoryException
	 */
	public function Build($className) {
		$mapperClass = $this->mapperNamespace.$className;
		if (class_exists($mapperClass)) {
			/** @var Mapper $mapper */
			//new Mapper($this, $this->reader, $this->writer);
			$mapper = new $mapperClass($this, $this->reader, $this->writer);
		} else {
			throw new MapperFactoryException(sprintf('The mapper could not be found: "%s"', $mapperClass));
		}
		return $mapper;
	}
}
