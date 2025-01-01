# Controllers

Controllers in Rebar serve as the backbone for handling business logic and responding to HTTP requests. Each controller maps incoming requests to specific methods, processes data, and determines how the response is rendered.

## Structure of a Controller

Controllers in Rebar extend the base class `Fluxoft\Rebar\Http\Controller`. They can define methods that correspond to HTTP request paths and actions, enabling a clean separation of logic.

### Anatomy of a Controller
Here’s an example of a controller from the RebarBase application:

```php
namespace RebarBase\Controllers;

use Fluxoft\Rebar\Container;
use Fluxoft\Rebar\Http\Controller;
use Fluxoft\Rebar\Http\Presenters\Json;
use Fluxoft\Rebar\Http\Presenters\Phtml;
use Fluxoft\Rebar\Http\Presenters\PresenterInterface;
use RebarBase\Services\MaterialsService;

class Materials extends Controller {
    private MaterialsService $service;

    /** @var Phtml */
    protected ?PresenterInterface $presenter;

    public function Setup(Container $container): void {
        $this->service   = $container['MaterialsService'];
        $this->presenter = $container['PhtmlPresenter'];
    }

    public function Default(): void {
        $materials = $this->service->FetchAll();

        $this->set('title', 'All Materials');
        $this->set('materials', $materials);

        $json = $this->request->Get('json');
        if ($json) {
            $this->presenter = new Json();
        } else {
            $this->presenter->Template = 'materials/default.phtml';
        }
    }

    public function View($id = null): void {
        if ($id === null) {
            $this->response->Halt(400, 'Bad Request: No ID provided.');
        }

        $material = $this->service->Fetch((int) $id);

        if ($material === null) {
            $this->response->Halt(404, 'Material not found.');
        }

        $this->set('title', $material->Name);
        $this->set('material', $material);

        $this->presenter->Template = 'materials/view.phtml';
    }

    public function Edit($id = null): void {
        $material = $id !== null ? $this->service->Fetch((int) $id) : null;

        $this->set('title', $material ? 'Edit Material: ' . $material->Name : 'Add New Material');
        $this->set('material', $material);

        $this->presenter->Template = 'materials/edit.phtml';
    }

    public function Save(): void {
        $Id        = $this->request->Post('Id');
        $Name      = $this->request->Post('Name');
        $Quantity  = (int) $this->request->Post('Quantity');
        $UnitPrice = (float) $this->request->Post('UnitPrice');

        $data = compact('Name', 'Quantity', 'UnitPrice');

        if ($Id) {
            $this->service->Update($Id, $data);
        } else {
            $this->service->Create($data);
        }

        $this->response->Redirect('/materials');
    }

    public function Delete($id = null): void {
        if ($id === null) {
            $this->response->Halt(400, 'Bad Request: No ID provided.');
        }

        try {
            $this->service->Delete((int) $id);
            $this->response->Redirect('/materials');
        } catch (\InvalidArgumentException $e) {
            $this->response->Halt(404, 'Material not found.');
        } catch (\Exception $e) {
            $this->response->Halt(500, 'An error occurred while deleting the material.');
        }
    }
}
```

## Key Features of Controllers

### Lifecycle Methods: `Setup` and `Cleanup`
- **`Setup`:** (Optional) Called before any action method. Use this to initialize resources such as services or dependencies. Arguments to this method can be configured as part of Router instantiation.
- **`Cleanup`:** (Optional) Called after the action method. Use this to release resources or perform teardown logic.

### Parameters and Request Handling
Controllers have access to the request and response objects, which allow them to interact with query parameters, POST data, and more:

- **Query Parameters:** Accessed via `$this->request->Get('key')`.
- **POST Data:** Accessed via `$this->request->Post('key')`.
- **Dynamic Parameters:** Passed automatically by the router to action methods based on the URL path.

### Presenters
Controllers delegate rendering to presenters (e.g., `Phtml` for HTML or `Json` for API responses). Switching presenters is straightforward:

```php
$this->presenter = new Json();
```

## Building and Returning Responses

Controllers interact with the response object to:
- Set headers: `$this->response->AddHeader('Content-Type', 'text/html');`
- Halt execution: `$this->response->Halt(404, 'Not Found');`
- Redirect: `$this->response->Redirect('/path');`

### Example: Rendering with Data
```php
$this->set('key', 'value'); // Add data to be rendered.
$this->presenter->Template = 'template/path.phtml';
```

## Best Practices
- **Separation of Concerns:** Keep business logic in services and use controllers for routing and request handling.
- **Use `Setup`:** Centralize initialization of dependencies or shared resources.
- **Switch Presenters as Needed:** Flexibly render responses in different formats (e.g., HTML vs. JSON).

With these principles, Rebar controllers provide a powerful way to organize and simplify your application’s logic.

Next Topic: [Data Mapping](data-mapping.md)
