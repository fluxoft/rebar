<?php

namespace Fluxoft\Rebar\Tests;

use Fluxoft\Rebar\Config;
use Fluxoft\Rebar\ConfigSourcesLoader;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase {
	public function testLoadFromArray(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadArray', ['app' => ['env' => 'local']]);

		$config = new Config(['array' => ['app' => ['env' => 'production']]], $configSourcesLoader);

		$this->assertSame('local', $config->app['env']);
	}

	public function testLoadFromIni(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadIni', ['app' => ['env' => 'local']]);

		$config = new Config(['ini' => '/mock/path/config.ini'], $configSourcesLoader);

		$this->assertSame('local', $config->app['env']);
	}

	public function testLoadFromJson(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadJson', ['app' => ['env' => 'production']]);

		$config = new Config(['json' => '/mock/path/config.json'], $configSourcesLoader);

		$this->assertSame('production', $config->app['env']);
	}

	public function testLoadFromDotenv(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadDotenv', ['DB_HOST' => 'localhost']);

		$config = new Config(['dotenv' => '/mock/path/.env'], $configSourcesLoader);

		$this->assertSame('localhost', $config->DB_HOST);
	}

	public function testLoadFromEnvironment(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadEnvironment', ['DB_PASSWORD' => 'secret']);

		$config = new Config(['env' => null], $configSourcesLoader);

		$this->assertSame('secret', $config->DB_PASSWORD);
	}

	public function testMergeOrder(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadIni', ['app' => ['env' => 'local']]);
		$configSourcesLoader::setMockData('LoadJson', ['app' => ['env' => 'production']]);

		$config = new Config(['ini' => '/mock/path/config.ini', 'json' => '/mock/path/config.json'], $configSourcesLoader);

		// JSON should overwrite INI values.
		$this->assertSame('production', $config->app['env']);
	}

	public function testInvalidSourcesThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			// @codingStandardsIgnoreLine
			"Invalid configuration sources provided: invalidSource1, invalidSource2. Allowed sources are: array, ini, json, dotenv, env."
		);
	
		// Attempt to create a Config object with invalid sources
		$sources = [
			'invalidSource1' => '/path/to/invalid1',
			'invalidSource2' => '/path/to/invalid2'
		];
	
		new Config($sources);
	}
}

// @codingStandardsIgnoreStart
class ConfigSourcesLoaderProxy_ConfigTest extends ConfigSourcesLoader {
	private static array $mockData = [];
	public static function setMockData(string $method, array $data): void {
		self::$mockData[$method] = $data;
	}

	public static function LoadArray(array $data): array {
		return self::$mockData['LoadArray'] ?? $data;
	}

	public static function LoadIni(string $filePath): array {
		return self::$mockData['LoadIni'] ?? [];
	}

	public static function LoadJson(string $filePath): array {
		return self::$mockData['LoadJson'] ?? [];
	}

	public static function LoadDotenv(string $filePath): array {
		return self::$mockData['LoadDotenv'] ?? [];
	}

	public static function LoadEnvironment(): array {
		return self::$mockData['LoadEnvironment'] ?? [];
	}
}
// @codingStandardsIgnoreEnd
