# Getting Started with Rebar

Welcome to Rebar! This guide will help you get started with installing, configuring, and building your first application using the Rebar framework.

## Installation

### Using Composer
Rebar is available on [Packagist](https://packagist.org/packages/fluxoft/rebar). You can install it using Composer:

```bash
composer require fluxoft/rebar
```

### Setting Up Your Project
1. Create your project directory:
   ```bash
   mkdir my-rebar-app
   cd my-rebar-app
   ```

2. Install Rebar:
   ```bash
   composer require fluxoft/rebar
   ```

3. Set up the directory structure:
   ```bash
   mkdir -p src public config tests
   ```

   - `src`: Your application’s PHP files.
   - `public`: The web server's root directory (for example, where `index.php` lives).
   - `config`: Configuration files.
   - `tests`: Your PHPUnit test files.

## Basic Usage

### Hello World Example
Create a simple application:

1. Create a `public/index.php` file:
   ```php
   <?php
   require_once __DIR__ . '/../vendor/autoload.php';

   use Fluxoft\Rebar\Router;

   $router = new Router();
   $router->AddRoute('GET', '/', function () {
       return 'Hello, World!';
   });

   $router->Run();
   ```

2. Start a local PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```

3. Open [http://localhost:8000](http://localhost:8000) in your browser to see "Hello, World!".

### Directory Structure
Here’s a suggested structure for your project:

```
my-rebar-app/
├── app/
│   ├── bootstrap.php
│   ├── config.php
│   └── container.php
├── public/
│   └── index.php
├── src/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── tests/
│   └── ExampleTest.php
├── vendor/
└── composer.json
```

- `config/`: Configuration files, such as database settings.
- `public/`: Entry point for web requests.
- `src/`: Application-specific code.
- `tests/`: Unit and integration tests.

## Next Steps

Once you’ve set up your project, check out the following guides to dive deeper into Rebar:
- [Routing](routing.md): Learn how to define routes and handle requests.
- [Controllers](controllers.md): Use controllers to organize your application’s logic.
- [Models and Mappers](models-and-mappers.md): Manage your data and database interactions.
