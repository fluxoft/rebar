# Customizing Rebar

Rebar is designed to be a lightweight and extensible PHP framework. While it provides a solid foundation for building applications, it’s easy to adapt and extend its functionality to fit your unique needs. This guide provides a broad overview of how you can customize Rebar by implementing its various interfaces and extending core features.

## General Philosophy
Rebar’s modular design is based on a set of interfaces and loosely coupled components. This means that if there’s a feature or behavior you’d like to add, you can implement the appropriate interface or extend a base class. Rebar will work seamlessly with your custom implementation as long as it adheres to the framework’s expectations.

### Why Interfaces Matter
Many core components in Rebar, such as presenters, middleware, and notifiers, are defined by interfaces. By implementing these interfaces, you ensure your custom code integrates cleanly with the framework while maintaining flexibility to extend functionality.

## Custom Presenters
Presenters in Rebar handle how responses are formatted and delivered to clients. For example, you might want to add a presenter for a templating engine like Blade. To create a custom presenter, implement the `PresenterInterface`:

```php
namespace App\Presenters;

use Fluxoft\Rebar\Http\Presenters\PresenterInterface;

class BladePresenter implements PresenterInterface {
	private string $templatePath;

	public function __construct(string $templatePath) {
		$this->templatePath = $templatePath;
	}

	public function Render(string $template, array $data = []): string {
		// Integrate Blade rendering logic here
		return Blade::render($this->templatePath . $template, $data);
	}
}
```

Once implemented, you can use your custom presenter anywhere Rebar expects a `PresenterInterface` instance. For example, in your IoC container:

```php
$container['BladePresenter'] = new ContainerDefinition(BladePresenter::class, ['/path/to/templates/']);
```

## Custom Middleware
Middleware allows you to add logic that runs during the request lifecycle, such as authentication, logging, or input validation. To create custom middleware, implement the `MiddlewareInterface`:

```php
namespace App\Middleware;

use Fluxoft\Rebar\Http\MiddlewareInterface;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class LoggingMiddleware implements MiddlewareInterface {
	public function Process(Request $request, Response $response, callable $next): Response {
		// Log the incoming request
		Logger::info('Request received', ['path' => $request->Path]);

		// Continue to the next middleware
		return $next($request, $response);
	}
}
```

Add your middleware to the Router:

```php
$router->AddMiddleware(new LoggingMiddleware());
```

## Custom Notifiers
Rebar’s error-handling system uses `NotifierInterface` implementations to manage how unhandled exceptions and errors are reported. For example, you can create an `EmailNotifier` to send error details via email:

```php
namespace App\Error\Notifiers;

use Fluxoft\Rebar\Error\NotifierInterface;

class EmailNotifier implements NotifierInterface {
	public function Notify(\Throwable $t): void {
		mail('admin@example.com', 'Error Notification', $t->getMessage());
	}
}
```

Register your custom notifier in the error handler:

```php
ErrorHandler::Register([
	new EmailNotifier(),
	new TextNotifier(true),
]);
```

## Custom Data Mappers
If you’re working with a database and need custom logic for interacting with your models, you can extend Rebar’s database mappers. For example, you might add a `CustomUserMapper`:

```php
namespace App\Mappers;

use Fluxoft\Rebar\Data\Db\Mappers\MySql;

class CustomUserMapper extends MySql {
	protected array $propertyDbMap = [
		'Id' => 'id',
		'Name' => 'name',
		'Email' => 'email'
	];

	public function GetUsersByRole(string $role): array {
		return $this->GetSet([
			new Filter('role', '=', $role)
		]);
	}
}
```

## Best Practices for Customization
- **Follow Interface Contracts**: Ensure your custom classes implement the correct interface and adhere to its expectations.
- **Keep It Simple**: Avoid unnecessary complexity when adding custom behavior.
- **Test Your Code**: Write tests for custom components to ensure they work as expected within the framework.
- **Document Your Changes**: Include clear documentation for customizations so future developers understand their purpose and usage.

## When to Contribute Back
If you’ve built a custom component that might benefit the broader Rebar community, consider submitting a pull request to the [Rebar GitHub repository](https://github.com/fluxoft/rebar). Contributions help make Rebar more versatile and useful for everyone.

