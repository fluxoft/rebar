# Customizing Rebar

Rebar is designed to be a flexible framework that you can adapt to suit your specific needs. This guide provides an overview of ways you can customize Rebar to better fit your application.

## Overview
Customization in Rebar typically involves:
- Extending core classes.
- Overriding or adding new behaviors.
- Integrating third-party libraries.

## Extending Core Classes
Most of Rebar’s core components are built to be extended. For example:

### Extending the `Controller` Class
You can create custom base controllers by extending Rebar’s `Controller` class. This is useful for adding shared functionality across multiple controllers:

```php
use Fluxoft\Rebar\Http\Controller;

class BaseController extends Controller {
    protected function Setup(): void {
        // Common setup logic for all controllers.
    }

    protected function Cleanup(): void {
        // Common cleanup logic for all controllers.
    }
}
```

Then, have your application’s controllers extend `BaseController`:

```php
class HomeController extends BaseController {
    public function Index(): string {
        return 'Welcome to Rebar!';
    }
}
```

### Customizing the IoC Container
Rebar’s IoC container can be extended to add custom bindings or functionality:

```php
use Fluxoft\Rebar\IoC\Container;

class CustomContainer extends Container {
    public function BindSingleton(string $interface, $implementation): void {
        $this->Bind($interface, function() use ($implementation) {
            static $instance = null;
            if ($instance === null) {
                $instance = new $implementation();
            }
            return $instance;
        });
    }
}
```

## Overriding or Adding New Behaviors

### Middleware
Rebar’s middleware system allows you to inject custom logic into the request lifecycle. To add new middleware, implement the `MiddlewareInterface`:

```php
use Fluxoft\Rebar\Http\MiddlewareInterface;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

class CustomMiddleware implements MiddlewareInterface {
    public function Process(Request $request, Response $response): Response {
        // Add custom logic here.
        return $response;
    }
}
```

Then, add your middleware to the router or request handling pipeline.

### Custom Data Mappers
Rebar’s data mappers can be extended to handle custom database logic:

```php
use Fluxoft\Rebar\Data\Db\Mappers\MySql;

class CustomUserMapper extends MySql {
    protected array $propertyDbMap = [
        'Id' => 'id',
        'Name' => 'name',
        'Email' => 'email'
    ];

    public function GetUsersByStatus(string $status): array {
        return $this->GetSet([new Filter('status', '=', $status)]);
    }
}
```

## Integrating Third-Party Libraries

### Adding a Logger
Rebar supports PSR-3 logging. To integrate a library like Monolog:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('/path/to/logfile.log', Logger::DEBUG));

$errorHandler = new Fluxoft\Rebar\ErrorHandler($logger);
$errorHandler->Enable();
```

### Using a Template Engine
Rebar supports template engines like Twig or Smarty. Install the desired library via Composer, then use the corresponding presenter class:

```php
use Fluxoft\Rebar\Presenters\TwigPresenter;

$presenter = new TwigPresenter('/path/to/templates');
echo $presenter->Render('template.twig', ['name' => 'Joe']);
```

## Best Practices for Customization
- **Keep It Simple**: Avoid unnecessary complexity when customizing Rebar. Stick to small, well-defined changes.
- **Follow Standards**: Use PSR standards (e.g., PSR-4 autoloading, PSR-3 logging) to ensure compatibility with other libraries.
- **Document Your Changes**: Clearly document any customizations for future developers (or your future self).

## Additional Resources
- [PSR Standards Documentation](https://www.php-fig.org/psr/)
- [Rebar GitHub Repository](https://github.com/fluxoft/rebar)

For any questions or suggestions, feel free to open an issue on the Rebar GitHub repository.
