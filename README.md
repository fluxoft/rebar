## Rebar
Rebar is a lightweight, flexible PHP framework designed to give developers a solid **skeletal foundation** for their applications. Much like its namesake — reinforcing bars used to strengthen concrete — Rebar provides the essential structure around which developers can add their own **concrete implementations**.

Rebar is not meant to be all things to all people. Instead, it focuses on:
- **Simplicity**: No hidden magic or bloat. Clear, explicit code that’s easy to understand and extend.
- **Flexibility**: A minimal but extendable framework that lets you shape it to your needs.
- **Stability**: Providing a dependable core with tools to reinforce your application’s architecture.

By emphasizing **abstract and concrete classes**, Rebar empowers developers to build applications on their own terms, offering just enough structure without getting in the way.

---

[![CircleCI](https://dl.circleci.com/status-badge/img/gh/fluxoft/rebar/tree/master.svg?style=svg)](https://dl.circleci.com/status-badge/redirect/gh/fluxoft/rebar/tree/master)
[![Latest Stable Version](https://poser.pugx.org/fluxoft/rebar/v/stable)](https://packagist.org/packages/fluxoft/rebar)
[![Coverage Status](https://coveralls.io/repos/github/fluxoft/rebar/badge.svg)](https://coveralls.io/github/fluxoft/rebar)
[![License](https://poser.pugx.org/fluxoft/rebar/license)](https://packagist.org/packages/fluxoft/rebar)

---

## Getting Started

### Installation
To get started, install Rebar via Composer:

```bash
composer require fluxoft/rebar
```

### Basic Example
Here’s a quick overview of how you might use Rebar in your project:

#### 1. Bootstrapping
Set up your application by initializing Rebar’s components (Router, IoC Container, etc.):

```php
use Fluxoft\Rebar\Http\Router;

// Initialize the router
$router = new Router();
$router->Add('GET', '/', function () {
    echo "Hello, World!";
});

// Run the router
$router->Route();
```

#### 2. Creating a Controller
Rebar follows a straightforward structure for creating controllers:

```php
use Fluxoft\Rebar\Http\Controller;

class ExampleController extends Controller {
    public function Index() {
        return ['message' => 'Welcome to Rebar!'];
    }
}
```

#### 3. Mapping Models to the Database
Rebar includes lightweight database mappers to make database interaction simple and efficient:

```php
use Fluxoft\Rebar\Data\Db\Mappers\MySql;

class UserMapper extends MySql {
    protected array $propertyDbMap = [
        'Id'       => 'id',
        'Username' => 'username',
        'Email'    => 'email'
    ];
}
```

---

## Documentation
Additional documentation is coming soon. A **starter project** using Rebar will be available to demonstrate best practices for structuring applications.
