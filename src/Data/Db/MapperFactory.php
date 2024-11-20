<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\Data\Db\Exceptions\MapperFactoryException;
use Fluxoft\Rebar\FactoryInterface;
use Fluxoft\Rebar\Model;
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;
use PDO;

/**
 * Class MapperFactory
 * @package Fluxoft\Rebar\Db
 */
abstract class MapperFactory implements FactoryInterface {
	/** @var PDO */
	protected PDO $reader;
	/** @var PDO */
	protected PDO $writer;
	/** @var string */
	protected string $mapperNamespace = '';
	/** @var string */
	protected string $modelNamespace = '';

	/**
	 * @param PDO $reader
	 * @param PDO|null $writer
	 */
	public function __construct(PDO $reader, PDO $writer = null) {
		$this->reader = $reader;
		$this->writer = $writer ?? $reader;
	}

	/**
	 * @param string $className
	 * @param array $extra Should be either ['model' => {\Fluxoft\Rebar\Data\Db\Model}]
	 *                     or ['modelClass' => 'ModelClass']
	 * @return GenericSql
	 * @throws MapperFactoryException
	 */
	public function Build(string $className, array $extra = []): GenericSql {
		// Mappers need the model to be mapped. If not given in $extra, make a guess based
		// on the name of the Mapper being constructed. If the $mapperName ends in "Mapper",
		// strip that word off and use what's left, e.g. a mapper called "ModelMapper" will
		// try to create a model called Model.
		$model = $extra['model'] ?? null;
		if (!isset($model)) {
			$modelClass = isset($extra['modelClass']) ?
				$this->modelNamespace.$extra['modelClass'] :
				null;
			if (!isset($modelClass)) {
				if (str_ends_with($className, 'Mapper')) {
					$modelClass = $this->modelNamespace.substr($className, 0, strlen($className) - 6);
				} else {
					$modelClass = $this->modelNamespace.$className;
				}
			}
			if (class_exists($modelClass)) {
				$model = new $modelClass();
			} else {
				throw new MapperFactoryException(sprintf(
					'The model could not be found: "%s"',
					$modelClass
				));
			}
		}
		if (!$model instanceof Model) {
			throw new MapperFactoryException(sprintf(
				'Model %s is not an instance of Model',
				get_class($model)
			));
		}

			$mapperClass = $this->mapperNamespace.$className;
		if (class_exists($mapperClass)) {
			/** @var Mapper $mapper */
			$mapper = new $mapperClass(
				$this,
				$model,
				$this->reader,
				$this->writer
			);
			if (!$mapper instanceof GenericSql) {
				throw new MapperFactoryException(sprintf(
					'Requested class %s is not an instance of Mapper',
					get_class($mapper)
				));
			}
		} else {
			throw new MapperFactoryException(sprintf(
				'The mapper could not be found: "%s"',
				$mapperClass
			));
		}
		return $mapper;
	}
}
