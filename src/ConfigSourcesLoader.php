<?php

namespace Fluxoft\Rebar;

/**
 * Class ConfigSourcesLoader
 * 
 * @package Fluxoft\Rebar
 */
class ConfigSourcesLoader {
	/**
	 * Load configuration from an array.
	 * @codeCoverageIgnore
	 */
	public static function LoadArray(array $array): array {
		return $array;
	}

	/**
	 * Load configuration from an INI file.
	 * @codeCoverageIgnore
	 */
	public static function LoadIni(string $filePath): array {
		if (!file_exists($filePath)) {
			throw new \InvalidArgumentException("INI file not found: $filePath");
		}
		return parse_ini_file($filePath, true) ?? [];
	}

	/**
	 * Load configuration from a JSON file.
	 * @codeCoverageIgnore
	 */
	public static function LoadJson(string $filePath): array {
		if (!file_exists($filePath)) {
			throw new \InvalidArgumentException("JSON file not found: $filePath");
		}
		$content = file_get_contents($filePath);
		return json_decode($content, true) ?? [];
	}

	/**
	 * Load configuration from a .env file.
	 *
	 * Supports:
	 * - Ignoring lines starting with `#` (comments).
	 * - Trimming quotes around values (`"value"` or `'value'`).
	 * - Handling escaped characters (e.g., `FOO="bar\nbaz"`).
	 *
	 * @param string $filePath Path to the .env file.
	 * @return array Associative array of environment variables.
	 * @throws \InvalidArgumentException If the file does not exist or has invalid lines.
	 */
	public static function LoadDotenv(string $filePath): array {
		$lines = static::loadDotenvFile($filePath);
		$env   = [];
		foreach ($lines as $line) {
			// Skip comments and empty lines.
			if (strpos(trim($line), '#') === 0 || trim($line) === '') {
				continue;
			}
	
			// Parse key-value pairs.
			if (preg_match('/^\s*([\w.]+)\s*=\s*(.*)\s*$/', $line, $matches)) {
				$key   = $matches[1];
				$value = $matches[2];
	
				// Remove surrounding quotes if present.
				if (preg_match('/^"(.*)"$/', $value, $quoted)) {
					$value = stripcslashes($quoted[1]); // Unescape characters within double quotes.
				} elseif (preg_match("/^'(.*)'$/", $value, $quoted)) {
					$value = $quoted[1]; // Keep single-quoted strings as-is.
				} else {
					$value = trim($value); // Handle unquoted values.
				}
	
				$env[$key] = $value;
			} else {
				throw new \InvalidArgumentException("Invalid line in .env file: $line");
			}
		}
	
		return $env;
	}
	
	

	/**
	 * Wrapper for the file() function to facilitate testing.
	 * @codeCoverageIgnore
	 */
	protected static function loadDotenvFile(string $filePath): array {
		if (!file_exists($filePath)) {
			throw new \InvalidArgumentException("File not found: $filePath");
		}
		return file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
	}


	/**
	 * Load configuration from environment variables.
	 * @codeCoverageIgnore
	 */
	public static function LoadEnvironment(): array {
		return $_ENV;
	}
}
