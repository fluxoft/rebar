# Routing

Routing in Rebar allows for a flexible approach to handling HTTP requests. You can define custom routes explicitly or rely on **intuitive routing** for paths that follow predictable naming patterns. This flexibility makes it easy to configure simple or complex routing setups.

## Overview

Rebar's router maps incoming requests to controllers and actions. This can be done via:
1. **Definition-Based Routing**: Explicitly defining routes.
2. **Intuitive Routing**: Allowing the framework to automatically resolve routes based on naming conventions.

## Definition-Based Routing

### How It Works
You can explicitly define routes in a dedicated routes configuration file (e.g., `app/routes.php`) or directly in your bootstrap file. Routes are defined as instances of the `Route` class, which requires the following:
- **Path**: The request path (e.g., `/materials/view`).
- **Controller**: The name of the controller (e.g., `Materials`).
- **Action**: The method in the controller (e.g., `View`).

### Example
`app/routes.php`:
```php
<?php

namespace RebarBase;

use Fluxoft\Rebar\Http\Route;

$routes = [];

$routes[] = new Route('/', 'Hello', 'World');
$routes[] = new Route('/materials/view', 'Materials', 'View');
$routes[] = new Route('/materials/edit', 'Materials', 'Edit');

return $routes;
```

In this example:
- `GET /` maps to the `World` method of the `Hello` controller.
- `GET /materials/view` maps to the `View` method of the `Materials` controller.
- `GET /materials/edit` maps to the `Edit` method of the `Materials` controller.

### Avoid Redundant Routes
It is important to note that the route `/materials/view` is redundant in this case. Rebar's **intuitive routing** would resolve this path automatically, as described below.

### Using Routes in Bootstrap
In the `app/bootstrap.php` file, you load and add routes like this:
```php
$routes = require_once __DIR__ . '/routes.php';
$router->AddRoutes($routes);
```

Alternatively, you can add individual routes directly:
```php
$router->AddRoute('/', 'Hello', 'World');
```

## Intuitive Routing

Intuitive routing allows you to rely on naming conventions to resolve paths to controllers and methods without explicitly defining routes. This can save time for straightforward routes.

### How It Works
1. The router interprets the request path (e.g., `/materials/view/1`).
2. It maps the first segment (`materials`) to the `Materials` controller.
3. It maps the second segment (`view`) to the `View` method in that controller.
4. Any additional segments (e.g., `1`) are passed as arguments to the method.

### Nested Controllers
Rebar supports organizing controllers into subfolders and subnamespaces. For example:
- A request to `/example/materials/view/1` maps to `App\Controllers\Example\Materials::View`.
- This feature allows you to structure your controllers in a way that makes sense for your application.

### When to Use
- Use intuitive routing for predictable, conventional paths like `/controller/action/params`.
- Use definition-based routing for paths with special requirements (e.g., `/custom-path`, or paths requiring additional middleware).

## Route Parameters

### Dynamic Parameters
Rebar does not explicitly use dynamic parameters (e.g., `/materials/view/{id}`). Instead, once a valid controller and action combination is resolved, the remaining segments of the path are passed as parameters to the method. For example:
- A request to `/materials/view/42` will call `Materials::View` and pass `42` as the first parameter to the method.
- Specifying `/materials/view/{id}` explicitly is unnecessary and will not alter this behavior.

## Middleware

Middleware can be added globally to handle cross-cutting concerns such as authentication, logging, or input validation.

### Adding Middleware
Middleware can be added to the router using one of the following methods:
1. `AddMiddleware(MiddlewareInterface $middleware)`: Adds a single middleware instance to the stack.
2. `SetMiddlewareStack(array $middlewareStack)`: Replaces the entire middleware stack.

Each middleware instance must implement the `MiddlewareInterface` with a `Process` method to modify and return the `Request` object.

### Route-Specific Middleware
Rebar does not currently support route-specific middleware. Middleware should handle its own logic to determine whether it applies to a given route.

## Defaults

If no controller or action is specified in the request path, Rebar defaults to:
- **Controller**: `Main`
- **Action**: `Default`

For example:
- A request to `/` resolves to `Main::Default`.

## Advanced Topics

### Custom Route Loading
You can organize routes into groups by creating multiple route files and loading them conditionally in `app/bootstrap.php`:
```php
$routes = array_merge(
    require_once __DIR__ . '/routes/admin.php',
    require_once __DIR__ . '/routes/api.php',
    require_once __DIR__ . '/routes/web.php'
);
$router->AddRoutes($routes);
```

## Summary

Rebar’s routing system is powerful yet simple, offering intuitive routing for most use cases and explicit routing for more advanced needs. By leveraging both approaches, you can build flexible, maintainable routing structures for any application.

Next Topic: [Controllers](controllers.md)
