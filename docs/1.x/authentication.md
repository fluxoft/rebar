# Authentication

Rebar’s authentication system is designed to integrate seamlessly into your application while offering flexibility for both web-based and API-based authentication workflows. This document provides an overview of the key components and the steps required to configure and implement authentication in your Rebar application.

## Overview of Authentication
Rebar’s authentication system includes:

- **Middleware:** The `Auth` middleware handles authentication for incoming requests.
- **AuthInterface Implementations:** The framework includes `WebAuth` and `ApiAuth` for common use cases, and you can create custom implementations if needed.
- **User Models and Mappers:** A user model implementing `UserInterface` and a user mapper implementing `UserMapperInterface` are required.

## Key Components

### Middleware
The `Auth` middleware ensures only authenticated requests can access specific paths. It integrates with `AuthInterface` implementations to authenticate users.

#### Adding Middleware to the Router
To protect routes with authentication, add the `Auth` middleware to the router:

```php
use Fluxoft\Rebar\Http\Middleware\Auth;

$authConfig = [
    '/api' => $apiAuthInstance,
    '/web' => $webAuthInstance
];
$authMiddleware = new Auth($authConfig);

$router = new Router();
$router->AddMiddleware($authMiddleware);
```

#### Configuring Auth Middleware
The `Auth` middleware takes an array of paths mapped to their respective `AuthInterface` implementations. The paths are checked in descending specificity (e.g., `/api/private` is matched before `/api`).

```php
$authMiddleware->SetAuthForPath($customAuthInstance, '/custom-path');
```

### AuthInterface
The `AuthInterface` defines the methods required for authentication classes:

```php
interface AuthInterface {
    public function GetAuthenticatedUser(Request $request): Reply;
    public function Logout(Request $request): Reply;
}
```

#### WebAuth Example
`WebAuth` handles cookie- and session-based authentication for web applications:

```php
use Fluxoft\Rebar\Auth\WebAuth;
use Fluxoft\Rebar\Auth\TokenManager;
use MyApp\Mappers\UserMapper;

$webAuth = new WebAuth(
    new UserMapper(),
    new TokenManager(),
    true // Use session-based tokens
);
```

### UserInterface
Your user model must implement `UserInterface` to integrate with Rebar’s authentication system:

```php
interface UserInterface {
    public function GetID(): int;
    public function GetAuthUsernameProperty(): string;
}
```

#### User Model Example
```php
use Fluxoft\Rebar\Auth\UserInterface;

class User implements UserInterface {
    private int $id;
    private string $username;

    public function __construct(int $id, string $username) {
        $this->id = $id;
        $this->username = $username;
    }

    public function GetID(): int {
        return $this->id;
    }

    public function GetAuthUsernameProperty(): string {
        return 'username';
    }
}
```

### UserMapperInterface
To connect your user model to a database, implement the `UserMapperInterface`. Rebar includes a `UserMapperTrait` to simplify this:

```php
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Auth\UserMapperTrait;

class UserMapper implements UserMapperInterface {
    use UserMapperTrait;

    public function GetAuthorizedUserById(int $id): ?UserInterface {
        // Fetch user from database
        return new User($id, 'exampleUser');
    }

    public function ValidateCredentials(string $username, string $password): ?UserInterface {
        // Validate credentials against database
        return new User(1, $username);
    }
}
```

## Setting Up Authentication

1. **Create a User Model:** Implement `UserInterface` to define your user object.
2. **Create a User Mapper:** Implement `UserMapperInterface` to fetch users and validate credentials.
3. **Choose an Auth Implementation:** Use `WebAuth` for web applications or `ApiAuth` for APIs.
4. **Configure Auth Middleware:** Add the `Auth` middleware to your router with appropriate path configurations.

## Example: Web-Based Authentication

```php
use Fluxoft\Rebar\Auth\WebAuth;
use Fluxoft\Rebar\Auth\TokenManager;
use Fluxoft\Rebar\Http\Middleware\Auth;
use Fluxoft\Rebar\Http\Router;
use MyApp\Mappers\UserMapper;

// Create Auth Implementation
$webAuth = new WebAuth(
    new UserMapper(),
    new TokenManager(),
    true
);

// Add Middleware to Router
$router = new Router();
$authMiddleware = new Auth([
    '/secure' => $webAuth
]);
$router->AddMiddleware($authMiddleware);

// Define Routes
$router->AddRoute('/secure', 'SecureController', 'Index');
```

## Best Practices
- Use secure, random keys for token signing.
- Set appropriate expiration times for access and refresh tokens.
- Store sensitive data (like refresh tokens) securely.
- Regularly review and revoke unused tokens.

## Advanced Topics

For advanced use cases, you can:
- Create custom `AuthInterface` implementations for unique authentication workflows.
- Extend `WebAuth` or `ApiAuth` for additional functionality.
- Use middleware to apply conditional authentication logic.

For more information, see the [Router Documentation](routing.md).

Next Topic: [Container](container.md)
