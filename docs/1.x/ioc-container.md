# IoC Container

The Inversion of Control (IoC) Container in Rebar is a lightweight dependency injection system that helps manage the instantiation and configuration of objects in your application.

## Overview
The IoC Container simplifies dependency management by allowing you to register and resolve services in a centralized way. It ensures consistency and makes testing and extending your application easier.

## Key Features
- **Service Registration**: Define how specific classes or interfaces should be resolved.
- **Lazy Loading**: Objects are only instantiated when needed.
- **Singleton Support**: Easily register shared instances of objects.
- **Custom Instantiation**: Define custom logic for creating services.

## Using the IoC Container

### Registering Services
You can register services with the IoC Container using the `Set` method:

```php
$container = new IoCContainer();

// Register a class
$container->Set(MyService::class, function() {
    return new MyService();
});

// Register an interface with a specific implementation
$container->Set(MyInterface::class, function() {
    return new MyConcreteClass();
});
```

### Resolving Services
To resolve a service, use the `Get` method:

```php
$service = $container->Get(MyService::class);

// The returned instance is of type MyService
```

If the service has not been registered, the IoC Container will attempt to resolve it automatically if the class is instantiable without dependencies or if dependencies can be resolved.

### Singleton Services
To ensure a service is only instantiated once, use the `SetSingleton` method:

```php
$container->SetSingleton(MyService::class, function() {
    return new MyService();
});

// The same instance is returned every time
$service1 = $container->Get(MyService::class);
$service2 = $container->Get(MyService::class);
assert($service1 === $service2);
```

### Custom Instantiation Logic
You can define custom instantiation logic for services with complex dependencies:

```php
$container->Set(MyService::class, function() use ($container) {
    $dependency = $container->Get(MyDependency::class);
    return new MyService($dependency);
});
```

## Best Practices
- **Centralized Registration**: Register all services in a central place (e.g., `app/container.php`).
- **Interface over Implementation**: Register interfaces instead of concrete classes to make your application more flexible.
- **Avoid Overuse**: Use the IoC Container for managing shared services, not for every object.

## Example: Setting Up a Container
Here’s an example of setting up a container for a web application:

```php
$container = new IoCContainer();

// Register database connection
$container->SetSingleton(PDO::class, function() {
    return new PDO('mysql:host=localhost;dbname=mydb', 'user', 'password');
});

// Register a repository
$container->Set(MyRepository::class, function() use ($container) {
    $pdo = $container->Get(PDO::class);
    return new MyRepository($pdo);
});

// Register a service
$container->Set(MyService::class, function() use ($container) {
    $repository = $container->Get(MyRepository::class);
    return new MyService($repository);
});

// Resolve a service
$service = $container->Get(MyService::class);
```

For a deeper dive into dependency injection patterns, check out [Customizing Rebar](customizing-rebar.md).
