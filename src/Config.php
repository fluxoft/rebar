<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\_Traits\ArrayAccessibleProperties;
use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\UnsettableProperties;

/**
 * Class Config
 * A centralized repository for configuration values from multiple sources.
 * Supports environment variables, .env files, .ini files, JSON files, and arrays.
 */
class Config implements \ArrayAccess {
	use GettableProperties, UnsettableProperties, ArrayAccessibleProperties;

	private static ?Config $instance = null;
	private ConfigSourcesLoader $loader;

	private const ALLOWED_SOURCES = ['array', 'ini', 'json', 'dotenv', 'env'];

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct(array $sources, ?ConfigSourcesLoader $loader = null) {
		$this->loader = $loader ?? new ConfigSourcesLoader();
		$this->validateSources($sources);
		$this->properties = []; // Initialize properties array.

		$this->loadSources($sources);
	}

	/**
	 * Initializes the singleton instance if not already initialized.
	 * p
	 * @param array $sources
	 * @param ConfigSourcesLoader|null $loader
	 * @return Config
	 */
	public static function Instance(array $sources = [], ?ConfigSourcesLoader $loader = null): Config {
		if (self::$instance === null) {
			if (empty($sources)) {
				throw new \LogicException("Config::Instance must be initialized with sources on the first call.");
			}
			self::$instance = new self($sources, $loader);
		}
		return self::$instance;
	}

	/**
	 * Resets the singleton instance. Useful for testing or reinitializing.
	 */
	public static function Reset(): void {
		self::$instance = null;
	}

	/**
	 * Loads sources into the configuration.
	 *
	 * @param array $sources
	 */
	private function loadSources(array $sources): void {
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
