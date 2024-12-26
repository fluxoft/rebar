# Configuration

Rebar provides a lightweight configuration system to manage settings for your application. The configuration system is designed to be simple, flexible, and easy to use.

## Overview
The `Config` class allows you to:
- Define application settings in a structured way.
- Access configuration values throughout your code.
- Load settings from multiple sources (e.g., arrays, files).

## Key Features
- **Centralized Configuration**: Manage all your application settings in one place.
- **Hierarchical Structure**: Use nested arrays or objects to organize configuration data.
- **Immutable Access**: Retrieve configuration values without modifying them.

## Setting Up Configuration

### Defining Configuration
Create a configuration file (e.g., `app/config.php`) to define your settings:

```php
return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'mydb',
        'user' => 'user',
        'password' => 'password'
    ],
    'app' => [
        'debug' => true,
        'timezone' => 'UTC'
    ]
];
```

### Loading Configuration
Use the `Config` class to load your configuration:

```php
use Fluxoft\Rebar\Config;

$configArray = include 'app/config.php';
$config = new Config($configArray);
```

## Accessing Configuration Values
You can retrieve configuration values using the `Get` method:

```php
$host = $config->Get('database.host'); // Returns 'localhost'
$debug = $config->Get('app.debug');    // Returns true
```

If the key does not exist, `Get` will return `null` by default:

```php
$nonExistent = $config->Get('nonexistent.key'); // Returns null
```

You can also provide a default value:

```php
$nonExistent = $config->Get('nonexistent.key', 'default'); // Returns 'default'
```

## Best Practices

### Keep Secrets Secure
Avoid storing sensitive data (e.g., passwords, API keys) directly in your configuration files. Use environment variables or a secrets manager where possible.

### Use a Consistent Structure
Organize your configuration data hierarchically to make it easier to manage and understand. For example:

```php
return [
    'database' => [
        'host' => 'localhost',
        'user' => 'user',
        'password' => 'password'
    ],
    'app' => [
        'name' => 'MyApp',
        'debug' => true
    ]
];
```

### Document Your Configuration
Provide comments or documentation for each setting to ensure other developers understand its purpose.

## Example: Using Configuration in a Service
Here’s an example of using configuration settings in a database service:

```php
use Fluxoft\Rebar\Config;

$configArray = include 'app/config.php';
$config = new Config($configArray);

class DatabaseService {
    private $pdo;

    public function __construct(Config $config) {
        $dbConfig = $config->Get('database');
        $dsn = sprintf('mysql:host=%s;dbname=%s', $dbConfig['host'], $dbConfig['name']);
        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
    }

    public function GetConnection(): PDO {
        return $this->pdo;
    }
}

$databaseService = new DatabaseService($config);
$connection = $databaseService->GetConnection();
```

## Advanced Topics

### Extending the Config Class
You can extend the `Config` class to add custom behavior, such as:
- Logging missing configuration keys.
- Automatically loading configuration from multiple files.

### Dynamic Configuration
Load configuration dynamically based on the environment:

```php
$environment = getenv('APP_ENV') ?: 'production';
$configArray = include "app/config.$environment.php";
$config = new Config($configArray);
```

For more details, see [Customizing Rebar](customizing-rebar.md).
