<?php

namespace Fluxoft\Rebar\Tests;

use Fluxoft\Rebar\Config;
use Fluxoft\Rebar\ConfigSourcesLoader;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		// Ensure a fresh instance for each test
		Config::Reset();
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Reset the singleton after each test to avoid contamination
		Config::Reset();
	}

	public function testLoadFromArray(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadArray', ['app' => ['env' => 'local']]);

		$config = Config::Instance(['array' => ['app' => ['env' => 'production']]], $configSourcesLoader);

		$this->assertSame('local', $config->app['env']);
	}

	public function testLoadFromIni(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadIni', ['app' => ['env' => 'local']]);

		$config = Config::Instance(['ini' => '/mock/path/config.ini'], $configSourcesLoader);

		$this->assertSame('local', $config->app['env']);
	}

	public function testLoadFromJson(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadJson', ['app' => ['env' => 'production']]);

		$config = Config::Instance(['json' => '/mock/path/config.json'], $configSourcesLoader);

		$this->assertSame('production', $config->app['env']);
	}

	public function testLoadFromDotenv(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadDotenv', ['DB_HOST' => 'localhost']);

		$config = Config::Instance(['dotenv' => '/mock/path/.env'], $configSourcesLoader);

		$this->assertSame('localhost', $config->DB_HOST);
	}

	public function testLoadFromEnvironment(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadEnvironment', ['DB_PASSWORD' => 'secret']);

		$config = Config::Instance(['env' => null], $configSourcesLoader);

		$this->assertSame('secret', $config->DB_PASSWORD);
	}

	public function testMergeOrder(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadIni', ['app' => ['env' => 'local']]);
		$configSourcesLoader::setMockData('LoadJson', ['app' => ['env' => 'production']]);

		$config = Config::Instance(
			['ini' => '/mock/path/config.ini', 'json' => '/mock/path/config.json'],
			$configSourcesLoader
		);

		// JSON should overwrite INI values.
		$this->assertSame('production', $config->app['env']);
	}

	public function testInvalidSourcesThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);
		$message  = "Invalid configuration sources provided: invalidSource1, ";
		$message .= "invalidSource2. Allowed sources are: array, ini, json, dotenv, env.";
		$this->expectExceptionMessage($message);

		// Attempt to initialize Config with invalid sources
		Config::Instance([
			'invalidSource1' => '/path/to/invalid1',
			'invalidSource2' => '/path/to/invalid2'
		]);
	}

	public function testAccessBeforeInitializationThrowsException(): void {
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage("Config::Instance must be initialized with sources on the first call.");

		// Attempt to access Config without initializing it
		Config::Instance()['some_setting'];
	}

	public function testMergePropertiesWithNestedArray(): void {
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigTest();
		$configSourcesLoader::setMockData('LoadArray', [
			'app' => [
				'env' => 'staging',
				'name' => 'RebarBase',
				'debug' => false,
				'database' => [
					'host' => 'localhost',
					'port' => 3306
				]
			]
		]);
		$configSourcesLoader::setMockData('LoadJson', [
			'app' => [
				'env' => 'production',
				'database' => [
					'port' => 5432,
					'user' => 'dbuser'
				]
			]
		]);

		$config = Config::Instance(
			['array' => ['app' => ['env' => 'staging']], 'json' => '/mock/path/config.json'],
			$configSourcesLoader
		);

		$expected = [
			'app' => [
				'env' => 'production', // Overwritten by JSON
				'name' => 'RebarBase', // Kept from array
				'debug' => false, // Kept from array
				'database' => [
					'host' => 'localhost', // Kept from array
					'port' => 5432, // Overwritten by JSON
					'user' => 'dbuser' // Added by JSON
				]
			]
		];
	
		$this->assertEquals($expected, $config->ToArray());
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
