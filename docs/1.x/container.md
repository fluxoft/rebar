# Using the Container in Rebar

Rebar includes a lightweight Dependency Injection Container (`Container`) to help you manage and organize your application’s dependencies. This guide explains how the `Container` works, how to define dependencies, and how to use it in your project.

## Overview

The `Container` allows you to:

- Define and manage dependencies using closures, scalars, or `ContainerDefinition` instances.
- Lazily instantiate objects when they are first accessed.
- Automatically resolve dependencies of complex objects.
- Use PSR-11-compatible methods (`get` and `has`) to access registered dependencies.
- Use array-style or object-style property access for added convenience.

---

## Core Components

### `Container`
The `Container` is the main class responsible for storing and resolving dependencies. It supports:

- **Array Access**: Use `$container['key']` to get or set dependencies.
- **Object Access**: Use `$container->key` for the same functionality.
- **PSR-11 Interface**: Use `$container->get($id)` and `$container->has($id)` for compatibility with the PSR-11 standard.

### `ContainerDefinition`
The `ContainerDefinition` class represents a complex dependency that requires a specific class and its dependencies to be resolved at runtime.

#### Example:
```php
new ContainerDefinition(\Namespace\ClassName::class, ['dependency1', 'dependency2']);
```

---

## Defining Dependencies

You can define dependencies in three main ways:

### 1. Scalar Values
Use scalar values or `null` directly in the container. These can represent configuration settings or constants.

#### Example:
```php
$container['DbConnectionString'] = 'sqlite:/path/to/database.db';
```

### 2. Closures
Use closures for complex instantiations. Closures receive the container instance as an argument, allowing you to access other dependencies.

#### Example:
```php
$container['DbWriter'] = function ($container) {
    $pdo = new \PDO($container['DbConnectionString']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
};
```

### 3. `ContainerDefinition`
Use `ContainerDefinition` for class instantiations that require dependency injection. Dependencies are resolved automatically based on the keys provided.

#### Example:
```php
$container['MaterialsMapper'] = new ContainerDefinition(
    \Namespace\MaterialsMapper::class,
    ['DbWriter', 'MaterialModel', 'DbReader']
);
```

---

## Loading Definitions

You can bulk load dependencies into the container using the `LoadDefinitions` method. This method processes an array of definitions, resolving aliases and `ContainerDefinition` instances.

### Example:
```php
$definitions = [
    'DbConnectionString' => 'sqlite:/path/to/database.db',
    'DbWriter'           => function ($container) {
        return new \PDO($container['DbConnectionString']);
    },
    'MaterialsMapper'    => new ContainerDefinition(
        \Namespace\MaterialsMapper::class,
        ['DbWriter', 'MaterialModel', 'DbReader']
    )
];

$container->LoadDefinitions($definitions);
```

---

## Accessing Dependencies

### Array Access
```php
$pdo = $container['DbWriter'];
```

### Object Access
```php
$pdo = $container->DbWriter;
```

### PSR-11 Interface
```php
if ($container->has('DbWriter')) {
    $pdo = $container->get('DbWriter');
}
```

---

## Example: Setting Up the Container

Here’s a complete example of setting up and using the `Container` in a Rebar project:

### Define Dependencies
```php
$container = new \Fluxoft\Rebar\Container();

$definitions = [
    // Database setup
    'DbConnectionString' => 'sqlite:' . __DIR__ . '/../data/app.db',
    'DbWriter'           => function ($c) {
        $pdo = new \PDO($c['DbConnectionString']);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    },
    'DbReader'           => 'DbWriter',

    // Mappers
    'MapperFactory'      => new \Fluxoft\Rebar\ContainerDefinition(
        \Namespace\MapperFactory::class,
        ['DbWriter']
    ),
    'MaterialsMapper'    => new \Fluxoft\Rebar\ContainerDefinition(
        \Namespace\MaterialsMapper::class,
        ['MapperFactory', 'MaterialModel', 'DbReader']
    ),

    // Models
    'MaterialModel'      => new \Fluxoft\Rebar\ContainerDefinition(
        \Namespace\Material::class
    ),

    // Services
    'MaterialsService'   => new \Fluxoft\Rebar\ContainerDefinition(
        \Namespace\MaterialsService::class,
        ['MaterialsMapper']
    ),

    // Presenters
    'TemplatePath'       => __DIR__ . '/../templates/',
    'PhtmlPresenter'     => new \Fluxoft\Rebar\ContainerDefinition(
        \Fluxoft\Rebar\Http\Presenters\Phtml::class,
        ['TemplatePath']
    ),
    'JsonPresenter'      => new \Fluxoft\Rebar\ContainerDefinition(
        \Fluxoft\Rebar\Http\Presenters\Json::class
    )
];

$container->LoadDefinitions($definitions);
```

### Access Dependencies
```php
$materialsService = $container['MaterialsService'];
$phtmlPresenter = $container->PhtmlPresenter;
```

---

## Best Practices

1. **Use `ContainerDefinition` for Classes**: Avoid directly instantiating objects outside the container to maintain consistency.
2. **Group Related Definitions**: Organize definitions logically (e.g., database, services, models).
3. **Avoid Overcomplicating**: Keep dependency definitions simple. Only use closures when necessary.
4. **Leverage Aliases**: Use string keys to alias dependencies and simplify configurations.

---

With Rebar’s `Container`, you can efficiently manage your dependencies, making your codebase cleaner, more modular, and easier to maintain.

Next Topic: [Configuration](configuration.md)
