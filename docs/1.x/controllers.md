# Controllers

Controllers in Rebar handle the business logic for your application. Each controller is associated with a specific route or group of routes and is responsible for processing the incoming request and returning an appropriate response.

## Structure of a Controller

Controllers in Rebar are classes that extend `Fluxoft\Rebar\Controller`. Each controller can define methods corresponding to HTTP request methods like `Get`, `Post`, `Put`, `Delete`, etc.

### Example
\`\`\`php
use Fluxoft\Rebar\Controller;

class MyController extends Controller {
	public function Get(array $params) {
		// Handle GET request
		return ['status' => 'success', 'data' => $params];
	}

	public function Post(array $params) {
		// Handle POST request
		return ['status' => 'success', 'message' => 'Data received'];
	}
}
\`\`\`

## Setup and Cleanup Methods

Controllers can optionally include `Setup` and `Cleanup` methods. These are executed before and after the main request handler, respectively.

- `Setup`: Use this to initialize or configure resources needed for the request.
- `Cleanup`: Use this to release resources or perform any final actions after the request has been processed.

### Example
\`\`\`php
class ExampleController extends Controller {
	public function Setup(array $params) {
		// Initialize database connection or logging
	}

	public function Cleanup(array $params) {
		// Close database connection or write log entry
	}

	public function Get(array $params) {
		return ['status' => 'success'];
	}
}
\`\`\`

## Parameters

The parameters passed to controller methods (including `Setup` and `Cleanup`) are determined by the router configuration. These parameters typically include:

- Route parameters extracted from the URL.
- Query string parameters.
- Any data provided in the request body (for POST, PUT, etc.).

## Returning a Response

Controller methods return data in array format, which is typically transformed by a presenter into the desired output format (e.g., JSON, HTML).

### Example
\`\`\`php
public function Get(array $params) {
	return [
		'status' => 'success',
		'message' => 'Welcome to Rebar!',
		'data' => $params
	];
}
\`\`\`

This would be converted into JSON or another format depending on the presenter used in your application.

---

Next up: the guide for [Data Mapping](data-mapping.md)
