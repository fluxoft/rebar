# Configuration Management in Rebar

The `Config` class in Rebar provides a centralized and flexible way to manage configuration values from multiple sources. It supports merging values from arrays, `.ini` files, `.json` files, `.env` files, and server environment variables, allowing you to adapt your application to different environments (e.g., development, testing, production).

## Overview

The `Config` class is built to:
- Load configuration values from various sources.
- Merge these values in a prioritized order.
- Provide a unified interface for accessing configuration values.

The `ConfigSourcesLoader` helper class handles loading configurations from specific sources and parsing the data.

## Supported Configuration Sources

The following sources are supported by the `Config` class:

| Source Type | Description |
|-------------|-------------|
| `array`     | An associative array of key-value pairs. |
| `ini`       | A `.ini` file. Supports sections and key-value pairs. |
| `json`      | A `.json` file. The content must be a valid JSON object. |
| `dotenv`    | A `.env` file containing key-value pairs (e.g., `KEY=VALUE`). |
| `env`       | Server environment variables accessed via `$_ENV`. |

## Setting Up the Config Class

To create a `Config` instance, pass an array of sources and their locations:

### Example: Loading Configurations

```php
use Fluxoft\Rebar\Config;

$sources = [
    'array' => [
        'AppName' => 'RebarApp',
        'DebugMode' => true
    ],
    'ini' => __DIR__ . '/config/settings.ini',
    'json' => __DIR__ . '/config/settings.json',
    'dotenv' => __DIR__ . '/.env',
    'env' => []
];

$config = new Config($sources);

// Accessing configuration values
echo $config['AppName']; // Outputs: RebarApp
```

## How It Works

The `Config` class processes the sources in the order specified in its constructor. Configuration values from later sources override those from earlier ones. This allows you to define defaults in a `.json` or `.ini` file and override them in a `.env` file or environment variables.

### Merging Sources

The configuration merging prioritizes sources in the order they are provided in the `$sources` array. For example, if both a `.json` file and environment variables define a `DB_HOST`, the value from environment variables will take precedence if `env` is processed later.

## Loading Configuration from Specific Sources

Here is how the `ConfigSourcesLoader` processes various source types:

### Array
```php
$sources = [
    'array' => [
        'DB_HOST' => 'localhost',
        'DB_PORT' => 3306
    ]
];
$config = new Config($sources);
```

### INI File
```ini
; settings.ini
[Database]
DB_HOST = localhost
DB_PORT = 3306
```
```php
$sources = [
    'ini' => __DIR__ . '/config/settings.ini'
];
$config = new Config($sources);
```

### JSON File
```json
{
  "DB_HOST": "localhost",
  "DB_PORT": 3306
}
```
```php
$sources = [
    'json' => __DIR__ . '/config/settings.json'
];
$config = new Config($sources);
```

### .env File
```
# .env
DB_HOST=localhost
DB_PORT=3306
```
```php
$sources = [
    'dotenv' => __DIR__ . '/.env'
];
$config = new Config($sources);
```

### Environment Variables

Set environment variables directly in the hosting environment:
```bash
export DB_HOST=localhost
export DB_PORT=3306
```
Then access them:
```php
$sources = [
    'env' => []
];
$config = new Config($sources);
```

## Example Use Case: Managing Database Configurations

### Development Environment
For local development, database credentials can be stored in a `.env` file:
```
# .env
DB_HOST=localhost
DB_USER=devuser
DB_PASS=devpass
```

### Production Environment
For production, credentials should be stored in environment variables:
```bash
export DB_HOST=prod-db-server
export DB_USER=produser
export DB_PASS=prodpass
```

### Unified Configuration Access
Define your configuration sources:
```php
$sources = [
    'dotenv' => __DIR__ . '/.env',
    'env' => []
];
$config = new Config($sources);
```
Use the `Config` object to retrieve database connection details:
```php
$dsn = sprintf('mysql:host=%s;dbname=%s', $config['DB_HOST'], $config['DB_NAME']);
$username = $config['DB_USER'];
$password = $config['DB_PASS'];
$pdo = new PDO($dsn, $username, $password);
```

## Testing vs. Production
In a testing environment, you can define a separate `.env` file or provide mock configurations using the `array` source:

### Example: Testing Setup
```php
$sources = [
    'array' => [
        'DB_HOST' => 'localhost',
        'DB_USER' => 'testuser',
        'DB_PASS' => 'testpass'
    ]
];
$config = new Config($sources);
```

In production, simply exclude the `array` source and rely on `.env` files or environment variables.

## Error Handling
The `Config` class ensures robust error handling:
- **File Not Found**: Throws an exception if a specified `.ini`, `.json`, or `.env` file does not exist.
- **Invalid File Content**: Throws an exception for malformed `.json` or `.env` files.

Example:
```php
try {
    $config = new Config(['json' => 'missing-file.json']);
} catch (FileNotFoundException $e) {
    echo $e->getMessage();
}
```

## Summary
The `Config` class offers a flexible and unified way to manage configuration values, making it easy to adapt your application to different environments. By leveraging prioritized sources, you can ensure consistency while allowing for environment-specific overrides. Whether in development, testing, or production, the `Config` class simplifies configuration management.

Next Topic: [Error Handling](error-handling.md)
