# Authentication

Authentication in Rebar provides the tools to secure your application by verifying the identity of users or services. It is designed to be flexible and integrate with various authentication mechanisms.

## Overview
Rebar’s authentication system supports different strategies, such as:

- **Token-based authentication**: For APIs or applications requiring stateless authentication.
- **Custom strategies**: Implement your own authentication logic to fit specific requirements.

## Key Concepts
### Auth Interface
The `AuthInterface` defines the core methods that any authentication class must implement:

```php
interface AuthInterface {
    public function Authenticate(Request $request): ?UserInterface;
    public function GetUser(): ?UserInterface;
}
```

### User Interface
The `UserInterface` represents an authenticated user. It includes methods for retrieving user-specific properties, such as:

```php
interface UserInterface {
    public function GetId(): mixed;
    public function GetAuthUsernameProperty(): string;
}
```

### TokenManager
For token-based authentication, the `TokenManager` class handles:

- Generating access tokens and refresh tokens.
- Validating tokens to ensure they haven’t expired or been tampered with.
- Revoking tokens for user logout or security purposes.

## Getting Started with Authentication
### Adding Authentication to Your Application
To include authentication in your application:

1. **Implement `AuthInterface`**:
   Create a class that defines how users are authenticated based on your application’s needs.

2. **Configure the Router**:
   Pass your authentication class to the router during initialization. For example:

   ```php
   $auth = new MyAuthClass();
   $router = new Router($auth);
   ```

3. **Protect Routes**:
   Middleware can be used to verify authentication before reaching certain routes.

   ```php
   $router->AddRoute(‘/secure’, SecureController::class, [$authMiddleware]);
   ```

## Token-based Authentication Example
### Generating a Token
Use the `TokenManager` to generate tokens for authenticated users:

```php
$tokenManager = new TokenManager($refreshTokenMapper, $claimsProvider, $secretKey);
$accessToken = $tokenManager->GenerateAccessToken($user);
```

### Validating a Token
Decode and validate tokens to ensure they are authentic and unexpired:

```php
try {
    $claims = $tokenManager->DecodeAccessToken($accessToken);
    // Proceed with authenticated actions
} catch (InvalidTokenException $e) {
    // Handle invalid or expired token
}
```

### Revoking a Token
Revoke refresh tokens as needed:

```php
$tokenManager->RevokeRefreshTokensByUserId($userId);
```

## Custom Authentication
To create a custom authentication system:

1. Implement the `AuthInterface` to define custom login or authentication rules.
2. Optionally, create a user model implementing the `UserInterface` for handling user-related data.
3. Integrate your authentication class into the application’s routing or middleware.

## Best Practices
- Always use secure, random keys for token signing.
- Set appropriate token expiration times to balance security and usability.
- Regularly review and revoke unused tokens to minimize security risks.

This is a high-level overview. See specific use cases and examples in the [Getting Started Guide](getting-started.md).
