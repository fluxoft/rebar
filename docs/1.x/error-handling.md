# Error Handling

Rebar provides a flexible error handling mechanism to manage exceptions and errors in your application. This system ensures that errors are logged, presented in a user-friendly way, and can be customized to fit your application's needs.

## Overview
The error handling system in Rebar is designed to:
- Log errors for debugging and monitoring purposes.
- Provide developers with detailed error messages during development.
- Display user-friendly error pages in production environments.

## Setting Up Error Handling

### Enabling Error Handling
In your `app/bootstrap.php`, enable error handling by setting up an error handler:

```php
use Fluxoft\Rebar\ErrorHandler;

// Set up the error handler
$errorHandler = new ErrorHandler();
$errorHandler->Enable();
```

### Configuring Error Levels
You can configure which error levels are reported and displayed. For example, to report all errors during development:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

In production, you might only log errors:

```php
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
```

## Customizing Error Pages
Rebar allows you to customize error pages for different HTTP status codes. This is especially useful for providing user-friendly messages for 404 (Not Found) or 500 (Internal Server Error).

### Example: Custom 404 Page
Create a `404.php` file in your `public` or `views` directory:

```php
<!DOCTYPE html>
<html>
<head>
    <title>Page Not Found</title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>Sorry, the page you are looking for does not exist.</p>
</body>
</html>
```

Then, configure your server or application to serve this page when a 404 error occurs.

## Logging Errors
Rebar integrates with PSR-3 compatible logging libraries, allowing you to log errors and exceptions. Use a logger such as Monolog to capture error details:

```php
use Fluxoft\Rebar\ErrorHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('application');
$logger->pushHandler(new StreamHandler('path/to/log/file.log', Logger::ERROR));

$errorHandler = new ErrorHandler($logger);
$errorHandler->Enable();
```

## Best Practices

### Separate Development and Production Settings
Use environment-specific settings to control error reporting and display:

- **Development**: Display detailed error messages and stack traces.
- **Production**: Log errors and display user-friendly messages.

### Regularly Monitor Logs
Review your error logs regularly to identify and address issues before they impact users.

### Test Error Handling
Test your error handling setup by simulating errors or exceptions to ensure they are logged and displayed correctly.

### Integrate with Monitoring Tools
Consider integrating your error logs with monitoring tools like Sentry or New Relic for real-time alerts and insights.

## Advanced Topics

### Handling Uncaught Exceptions
Rebar's `ErrorHandler` can handle uncaught exceptions globally. Ensure it is registered as the default exception handler:

```php
set_exception_handler([$errorHandler, 'HandleException']);
```

### Custom Error Handlers
You can extend the `ErrorHandler` class to add custom behavior, such as sending error notifications via email or Slack.

For more details on advanced error handling, see [Customizing Rebar](customizing-rebar.md).
