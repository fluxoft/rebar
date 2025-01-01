# Getting Started with Rebar

Welcome to Rebar! This guide will help you get started with installing, configuring, and building your first application using the Rebar framework.

## Installation

### Using `rebar-base`
The easiest way to get started with Rebar is to use the `rebar-base` starter project. It provides a pre-configured directory structure and basic examples to get you up and running quickly.

1. Create your project directory:
   ```bash
   composer create-project fluxoft/rebar-base my-rebar-app
   ```

2. Navigate to your new project directory:
   ```bash
   cd my-rebar-app
   ```

3. Start a local PHP development server:
   ```bash
   php -S localhost:8000 -t public
   ```

4. Open [http://localhost:8000](http://localhost:8000) in your browser to see the example materials application.

### Manual Setup
If you prefer to configure your project manually, follow these steps:

1. Create your project directory:
   ```bash
   mkdir my-rebar-app
   cd my-rebar-app
   ```

2. Install Rebar using Composer:
   ```bash
   composer require fluxoft/rebar
   ```

3. Set up the directory structure:
   ```bash
   mkdir -p src public config tests app
   ```

4. Create the `public/index.php` file:
   ```php
   <?php
   require_once __DIR__ . '/../app/bootstrap.php';
   ```

5. Create the `app/bootstrap.php` file to handle autoloading, dependency injection, and routing:
   ```php
   <?php

   namespace MyApp;

   use Fluxoft\Rebar\Error\{BasicNotifier, Handler};
   use Fluxoft\Rebar\Exceptions\{AuthenticationException, RouterException};
   use Fluxoft\Rebar\Http\{Environment, Request, Response, Router};

   error_reporting(E_ALL);
   ini_set('display_errors', 1);

   require_once __DIR__ . '/../vendor/autoload.php';

   // Load container and routes
   $container = require_once __DIR__ . '/container.php';
   $routes = require_once __DIR__ . '/routes.php';

   Handler::Handle(new BasicNotifier());

   $router = new Router(
       'MyApp\\Controllers\\',
       [$container]
   );
   $router->AddRoutes($routes);

   $request = new Request(Environment::GetInstance());
   $response = new Response();

   try {
       $router->Route($request, $response);
   } catch (RouterException $e) {
       $response->Halt(404, "Route not found\n" . $e->getMessage());
   } catch (AuthenticationException $e) {
       $response->Halt(401, "Authentication error\n" . $e->getMessage());
   } catch (\Exception $e) {
       $response->Halt(500, "An error occurred\n" . $e->getMessage());
   }
   ```

6. Set up additional files, such as `app/container.php` for dependency injection and `app/routes.php` for routing.

### Directory Structure
Whether you use `rebar-base` or set up your project manually, you should aim for the following structure:

```
my-rebar-app/
├── app/
│   ├── bootstrap.php       # Application bootstrap script.
│   ├── container.php       # Dependency injection container.
│   └── routes.php          # Application routes.
├── public/
│   └── index.php           # Entry point for web requests.
├── src/                    # Application-specific code.
│   ├── Controllers/        # Controllers.
│   ├── Models/             # Models.
│   └── Mappers/            # Data mappers.
├── tests/                  # Unit and integration tests.
│   └── ExampleTest.php     # Example test file.
├── vendor/                 # Composer dependencies.
├── data/                   # Example SQLite database.
├── setup/                  # Setup scripts for seeding or initializing data.
├── templates/              # Templates for presentation.
└── composer.json           # Composer configuration.
```

## Next Steps

Once you’ve set up your project, check out the following guides to dive deeper into Rebar:

- [Routing](routing.md): Learn how to define routes and handle requests.
- [Controllers](controllers.md): Use controllers to organize your application’s logic.
- [Models and Mappers](models-and-mappers.md): Manage your data and database interactions.

If you used `rebar-base`, explore the included example materials application to see Rebar in action!

Next Topic: [Routing](routing.md)

