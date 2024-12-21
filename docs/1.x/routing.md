# Routing

Routing in Rebar provides the flexibility to handle requests using either **definition-based routing** or **intuitive routing**, depending on the needs of your application.

## Overview

The Rebar router determines which controller and action should handle an incoming request. You can define routes explicitly or let the framework resolve them automatically based on your application's directory and file structure.

### Definition-Based Routing

Definition-based routing allows you to map specific HTTP methods and URIs to controllers and actions explicitly.

#### Example
```php
$router->AddRoute('GET', '/users', 'UserController', 'Index');
$router->AddRoute('POST', '/users', 'UserController', 'Create');
```

In this example:
- The `GET /users` route maps to the `Index` action of the `UserController`.
- The `POST /users` route maps to the `Create` action of the same controller.

### Intuitive Routing

Intuitive routing resolves requests automatically based on the path structure. For example, a request to `/users/index` maps to the `Index` action of the `UserController` without requiring explicit route definitions.

#### How It Works
1. The router interprets the request path (e.g., `/users/index`).
2. It matches the first segment (`users`) to the `UserController`.
3. It matches the second segment (`index`) to the `Index` action within that controller.

If no specific action is provided, the router attempts to call the default action (e.g., `Index`).

### Route Parameters

Both routing approaches support dynamic route parameters.

#### Example with Definition-Based Routing
```php
$router->AddRoute('GET', '/users/{id}', 'UserController', 'View');
```

In this example, `{id}` is a placeholder for a dynamic parameter. When a request like `/users/42` is made, the router passes `42` as an argument to the `View` action.

#### Example with Intuitive Routing
For a request like `/users/view/42`, the router will pass `42` as a parameter to the `View` action of `UserController`.

### Middleware

Middleware can be applied to routes to add functionality such as authentication, logging, or input validation.

#### Example
```php
$router->AddRoute('GET', '/dashboard', 'DashboardController', 'Index', [$authMiddleware]);
```

Here, the `$authMiddleware` ensures that only authenticated users can access the `/dashboard` route.

### Controllers and Lifecycle Methods

Controllers can include optional `Setup` and `Cleanup` methods. The `Setup` method runs before the main action, and the `Cleanup` method runs after the action is complete. These methods are ideal for initializing resources or handling teardown logic.

#### Parameters for `Setup` and `Cleanup`
Router configuration determines what parameters, if any, are passed to `Setup` and `Cleanup`. (For more details, see the [Controllers documentation](controllers.md)).

### Advanced Topics

#### Route Groups
Route groups allow you to organize routes with shared attributes (e.g., a common prefix or middleware).

#### Optional Parameters
Optional parameters can be included in routes. For instance:
```php
$router->AddRoute('GET', '/articles/{id?}', 'ArticleController', 'View');
```
Here, the `{id}` parameter is optional.

#### Default Parameter Values
Default values for route parameters can be defined:
```php
$router->AddRoute('GET', '/articles/{id}', 'ArticleController', 'View')->SetDefaults(['id' => 1]);
```
