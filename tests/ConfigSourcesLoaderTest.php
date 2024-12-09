<?php

namespace Fluxoft\Rebar\Tests;

use Fluxoft\Rebar\ConfigSourcesLoader;
use PHPUnit\Framework\TestCase;

class ConfigSourcesLoaderTest extends TestCase {
	/**
	 * Test the LoadDotenv method with various .env inputs.
	 */
	public function testLoadDotenv(): void {
		// Mock input for the .env file.
		$mockEnvLines = [
			"# Comment line",
			"APP_ENV=\"production\"",
			"DB_HOST=localhost",
			"DB_PASSWORD=\"pa\$sword\"",
			"DB_NAME='my_database'",
			"UNQUOTED=value",
			"EMPTY_VALUE="
		];
		
		$expectedEnv = [
			'APP_ENV'      => 'production',
			'DB_HOST'      => 'localhost',
			'DB_PASSWORD'  => 'pa$sword',
			'DB_NAME'      => 'my_database',
			'UNQUOTED'     => 'value',
			'EMPTY_VALUE'  => ''
		];
		
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigSourcesLoaderTest();
		$configSourcesLoader->SetFileLines($mockEnvLines);

		$env = $configSourcesLoader->LoadDotenv('/path/to/mock.env');

		// Assertions.
		$this->assertSame($expectedEnv, $env);
	}

	public function testLoadDotenvThrowsExceptionForInvalidLine(): void {
		// Mock input for the .env file with an invalid line.
		$mockEnvLines = [
			"INVALID_LINE_NO_EQUALS_SIGN",
			"APP_ENV=\"production\""
		];
	
		$configSourcesLoader = new ConfigSourcesLoaderProxy_ConfigSourcesLoaderTest();
		$configSourcesLoader->SetFileLines($mockEnvLines);
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("Invalid line in .env file: INVALID_LINE_NO_EQUALS_SIGN");
	
		// Trigger the LoadDotenv method, which should throw an exception.
		$configSourcesLoader->LoadDotenv('/path/to/mock.env');
	}
}

// @codingStandardsIgnoreStart
class ConfigSourcesLoaderProxy_ConfigSourcesLoaderTest extends ConfigSourcesLoader {
	protected static array $fileLines = [];
	public function SetFileLines(array $fileLines): void {
		self::$fileLines = $fileLines;
	}
	protected static function loadDotenvFile(string $filePath): array {
		return self::$fileLines;
	}
}
// @codingStandardsIgnoreEnd
