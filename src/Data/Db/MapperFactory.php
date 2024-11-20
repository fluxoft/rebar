<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\Data\Db\Exceptions\MapperFactoryException;
use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;
use Fluxoft\Rebar\FactoryInterface;
use Fluxoft\Rebar\Model;
use PDO;

/**
 * Class MapperFactory
 * @package Fluxoft\Rebar\Data\Db
 */
abstract class MapperFactory implements FactoryInterface {
	protected PDO $reader;
	protected PDO $writer;
	protected string $mapperNamespace = '';
	protected string $modelNamespace  = '';

	public function __construct(PDO $reader, PDO $writer = null) {
		$this->reader = $reader;
		$this->writer = $writer ?? $reader;
	}

	/**
	 * Build and return a Mapper instance.
	 *
	 * @param string $className Fully qualified name of the Mapper class.
	 * @param array $extra Options for creating the Mapper (e.g., model instance or class).
	 * @return MapperInterface
	 * @throws MapperFactoryException
	 */
	public function Build(string $className, array $extra = []): MapperInterface {
		// Construct the Mapper class name
		$mapperClass = $this->mapperNamespace . $className;
	
		// Ensure the Mapper class exists
		if (!class_exists($mapperClass)) {
			throw new MapperFactoryException(sprintf(
				'The mapper could not be found: "%s"',
				$mapperClass
			));
		}
	
		// Use reflection to ensure the mapper implements MapperInterface
		$mapperReflection = new \ReflectionClass($mapperClass);
		if (!$mapperReflection->implementsInterface(MapperInterface::class)) {
			throw new MapperFactoryException(sprintf(
				'Requested class %s does not extend MapperInterface',
				substr($mapperClass, 1)
			));
		}
	
		// Now resolve the model
		$model = $this->resolveModel($className, $extra);
	
		// Instantiate the mapper with the resolved model
		return new $mapperClass(
			$this,
			$model,
			$this->reader,
			$this->writer
		);
	}	

	/**
	 * Resolve the Model instance from the class name or extra options.
	 *
	 * @param string $className
	 * @param array $extra
	 * @return Model
	 * @throws MapperFactoryException
	 */
	private function resolveModel(string $className, array $extra): Model {
		$model = $extra['model'] ?? null;
	
		if (!isset($model)) {
			$modelClass = $extra['modelClass'] ?? $this->inferModelClass($className);
	
			// Check if the derived class exists
			if (!class_exists($modelClass)) {
				throw new MapperFactoryException(sprintf(
					'The model could not be found: "%s"',
					$modelClass
				));
			}
	
			$model = new $modelClass();
		}
	
		// Validate that the model is an instance of Model
		if (!$model instanceof Model) {
			throw new MapperFactoryException(sprintf(
				'Model %s is not an instance of Model',
				is_object($model) ? get_class($model) : $model
			));
		}
	
		return $model;
	}

	/**
	 * Infer the Model class name from the Mapper class name.
	 *
	 * @param string $mapperClass
	 * @return string
	 */
	protected function inferModelClass(string $mapperClass): string {
		if (str_ends_with($mapperClass, 'Mapper')) {
			return $this->modelNamespace . substr($mapperClass, 0, -6);
		}
		return $this->modelNamespace . $mapperClass;
	}
}
