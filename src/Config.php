<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\UnsettableProperties;
use Fluxoft\Rebar\Exceptions\FileNotFoundException;

/**
 * Class Config
 * A centralized repository for configuration values from multiple sources.
 * Supports environment variables, .env files, .ini files, JSON files, and arrays.
 */
class Config {
	use GettableProperties, UnsettableProperties, ArrayAccessibleProperties;

	private const ALLOWED_SOURCES = ['array', 'ini', 'json', 'dotenv', 'env'];

	private ConfigSourcesLoader $loader;

	/**
	 * Config constructor.
	 *
	 * @param array $sources Associative array of source types and their locations.
	 * @param ConfigSourcesLoader|null $loader A loader instance (used for testing).
	 * @throws FileNotFoundException|\InvalidArgumentException
	 */
	public function __construct(array $sources, ?ConfigSourcesLoader $loader = null) {
		$this->loader = $loader ?? new ConfigSourcesLoader();
		$this->validateSources($sources);
		$this->properties = []; // Initialize properties array.

		foreach (self::ALLOWED_SOURCES as $allowedSource) {
			if (array_key_exists($allowedSource, $sources)) {
				$location = $sources[$allowedSource];

				switch ($allowedSource) {
					case 'array': // @codeCoverageIgnore
						$this->mergeProperties($this->loader::loadArray($location));
						break;

					case 'ini': // @codeCoverageIgnore
						$this->mergeProperties($this->loader::loadIni($location));
						break;

					case 'json': // @codeCoverageIgnore
						$this->mergeProperties($this->loader::loadJson($location));
						break;

					case 'dotenv': // @codeCoverageIgnore
						$this->mergeProperties($this->loader::loadDotenv($location));
						break;

					case 'env': // @codeCoverageIgnore
						$this->mergeProperties($this->loader::loadEnvironment());
						break;
				}
			}
		}
	}

	private function validateSources(array $sources): void {
		$invalidSources = array_diff(array_keys($sources), self::ALLOWED_SOURCES);
		if (!empty($invalidSources)) {
			throw new \InvalidArgumentException(
				sprintf(
					"Invalid configuration sources provided: %s. Allowed sources are: %s.",
					implode(', ', $invalidSources),
					implode(', ', self::ALLOWED_SOURCES)
				)
			);
		}
	}

	private function mergeProperties(array $newProperties): void {
		$this->properties = array_merge($this->properties, $newProperties);
	}
}
