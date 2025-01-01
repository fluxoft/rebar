# Error Handling in Rebar

Rebar's error handling system is designed to provide a robust and flexible mechanism for managing uncaught exceptions and PHP errors. The system ensures that your application handles unexpected situations gracefully, whether by logging errors, displaying debug information, or notifying external systems.

## Overview
Rebar’s error handling is managed by the `ErrorHandler` class, which:
- Registers custom handlers for errors, exceptions, and fatal shutdowns.
- Supports a stack of notifiers to process errors in a defined order.
- Allows you to easily extend functionality by implementing the `NotifierInterface`.

## Setting Up Error Handling

To enable error handling in your application, use the `ErrorHandler::Register` method. This method accepts an array of notifiers, which are responsible for processing errors and exceptions.

### Example
```php
use Fluxoft\Rebar\Error\ErrorHandler;
use Fluxoft\Rebar\Error\Notifiers\TextNotifier;
use Fluxoft\Rebar\Error\Notifiers\HtmlNotifier;

// Array of notifiers to use for error handling (order matters).
$notifierStack = [];
$notifierStack[] = new LoggerNotifier(); // Logs the exception to a PSR-3 compliant logger.
$notifierStack[] = new HtmlNotifier(true); // HTML output with full stack trace in verbose mode.

// Register the error handler.
ErrorHandler::Register($notifierStack);
```

In this example:
- Errors will first be processed by the `LoggerNotifier`.
- The `HtmlNotifier` will handle the error, displaying it in a web-friendly format.

Note that because `HtmlNotifier` is writing output, it should be the last in the stack, and should not be used
with any other notifiers that write output like `TextNotifier`.

## Built-in Notifiers
Rebar provides several built-in notifiers, each implementing the `NotifierInterface`. These are located in the `Fluxoft\Rebar\Error\Notifiers` namespace.

### TextNotifier
Outputs errors in plain text format. This is ideal for command-line applications or logging purposes.
```php
use Fluxoft\Rebar\Error\Notifiers\TextNotifier;

$textNotifier = new TextNotifier(true); // Verbose mode enabled for stack traces.
```

### HtmlNotifier
Displays errors in an HTML format suitable for web applications.
```php
use Fluxoft\Rebar\Error\Notifiers\HtmlNotifier;

$htmlNotifier = new HtmlNotifier(false); // Minimal output (no stack trace).
```

### JsonNotifier
Displays errors encoded in JSON, suitable for use with APIs.
```php
use Fluxoft\Rebar\Error\Notifiers\JsonNotifier;

$htmlNotifier = new JsonNotifier(true); // Set to verbose to include stack trace and specific message.
```

### LoggerNotifier
Integrates with any PSR-3 compliant logger to log errors.
```php
use Fluxoft\Rebar\Error\Notifiers\LoggerNotifier;
use Psr\Log\LoggerInterface;

$logger = new YourCustomLogger(); // Any PSR-3 compliant logger.
$loggerNotifier = new LoggerNotifier($logger);
```

## How It Works

### ErrorHandler Class
The `ErrorHandler` is responsible for:
1. Setting PHP’s error, exception, and shutdown handlers.
2. Managing a stack of notifiers.
3. Delegating error and exception notifications to all registered notifiers in order.

#### Registering the Error Handler
Use the `Register` method to set up the error handling system:
```php
ErrorHandler::Register([$notifier1, $notifier2]);
```

#### Notifying All Notifiers
When an error or exception occurs, `ErrorHandler` calls the `Notify` method of each notifier in the stack. Execution order is determined by the order in which notifiers are added to the array passed to `Register`.

### Handling Fatal Errors
The `register_shutdown_function` ensures that fatal errors (e.g., syntax errors) are also caught and processed by the notifiers.

## Creating a Custom Notifier
To create your own notifier, implement the `NotifierInterface`:

```php
namespace YourNamespace;

use Fluxoft\Rebar\Error\NotifierInterface;

class CustomNotifier implements NotifierInterface {
	public function Notify(\Throwable $t): void {
		// Custom notification logic, e.g., send an email or post to an API.
		echo "Custom notification: ".$t->getMessage();
	}
}
```

Add your custom notifier to the `ErrorHandler` stack:
```php
$customNotifier = new CustomNotifier();
ErrorHandler::Register([$customNotifier, new HtmlNotifier()]);
```

## Best Practices
- **Keep Notifiers Focused**: Each notifier should handle a specific type of notification, such as logging, emailing, or displaying errors.
- **Order Matters**: Arrange notifiers in the array passed to `ErrorHandler::Register` based on their priority. For example, loggers should typically run before user-facing notifiers.
- **Test Your Notifiers**: Ensure that custom notifiers handle all edge cases, including empty messages and unexpected exceptions.
- **Verbose vs Minimal Output**: Use verbose output during development and testing. In production, prefer minimal output to avoid exposing sensitive details.

## Example Use Cases

### Development Environment
In development, use verbose output to debug issues:
```php
ErrorHandler::Register([
	new LoggerNotifier($logger),
	new HtmlNotifier(true)
]);
```

### Production Environment
In production, log errors and display minimal user-facing messages:
```php
ErrorHandler::Register([
	new LoggerNotifier($logger),
	new HtmlNotifier(false)
]);
```

With this setup, critical errors are logged, and users see a simple error message.

## Summary
Rebar’s error handling system is designed to be simple, extensible, and effective. With the `ErrorHandler` and the `NotifierInterface`, you can:
- Customize how errors and exceptions are managed.
- Integrate with logging systems and external monitoring tools.
- Provide meaningful feedback to developers and users.

For more advanced use cases, consider extending the `NotifierInterface` to meet your application’s unique requirements.

Next Topic: [Customizing Rebar](customizing-rebar.md)
