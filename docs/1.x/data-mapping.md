# Data Mapping

Rebar provides a flexible and powerful data mapping system to connect PHP objects (models) with database tables. The system uses mappers to handle CRUD operations and manage relationships between models and database data.

## Overview

The data mapping system in Rebar consists of:

- **Models**: Represent data entities in your application.
- **Mappers**: Provide the interface between models and the database.
- **MapperFactory**: Facilitates the creation of mapper instances.
- **Property Mapping**: Defines how model properties map to database columns.
- **Services**: Encapsulate business logic and interact with mappers.

## Creating a Model

Models in Rebar extend the `Fluxoft\Rebar\Model` class. Properties in models can include validation logic and calculated read-only values.

### Example

```php
namespace RebarBase\Models;

use Fluxoft\Rebar\Model;

/**
 * Class Material
 * @property int    $Id
 * @property string $Name
 * @property float  $Quantity
 * @property float  $UnitPrice
 * @property-read float $TotalValue
 */
class Material extends Model {
    protected static array $defaultProperties = [
        'Id'         => null,
        'Name'       => '',
        'Quantity'   => 0,
        'UnitPrice'  => 0.0,
        'TotalValue' => 0.0
    ];

    protected function getTotalValue() {
        return $this->Quantity * $this->UnitPrice;
    }

    protected function validateQuantity($value) {
        if (!is_numeric($value) || $value < 0) {
            return 'Quantity must be a number greater than or equal to 0.';
        }
        return true;
    }

    protected function validateUnitPrice($value) {
        if (!is_numeric($value) || $value < 0) {
            return 'Unit Price must be a number greater than or equal to 0.';
        }
        return true;
    }
}
```

## Creating a Mapper

Mappers in Rebar define how model properties are mapped to database columns. For best practices, extend a database-specific mapper class, such as `SQLite`, `Mysql`, or `Postgres`.

### Example

```php
namespace RebarBase\Mappers;

use Fluxoft\Rebar\Data\Db\Mappers\SQLite;

class MaterialsMapper extends SQLite {
    protected string $dbTable       = 'materials';
    protected string $idProperty    = 'Id';
    protected array  $propertyDbMap = [
        'Id'        => 'Id',
        'Name'      => 'Name',
        'Quantity'  => 'Quantity',
        'UnitPrice' => 'UnitPrice'
    ];
}
```

## Using the MapperFactory

The `MapperFactory` simplifies the creation of mappers by defining namespaces for your mappers and models.

### Example

```php
namespace RebarBase\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory as RebarMapperFactory;

class MapperFactory extends RebarMapperFactory {
    protected string $mapperNamespace = 'RebarBase\\Mappers\\';
    protected string $modelNamespace  = 'RebarBase\\Models\\';
}
```

## Encapsulating Logic in Services

Services interact with mappers and encapsulate business logic. Extend `Fluxoft\Rebar\Data\Db\AbstractService` to create services.

### Example

```php
namespace RebarBase\Services;

use Fluxoft\Rebar\Data\Db\AbstractService;
use RebarBase\Mappers\MaterialsMapper;

class MaterialsService extends AbstractService {
    public function __construct(MaterialsMapper $mapper) {
        parent::__construct($mapper);
    }
}
```

## CRUD Operations

### Creating a Record

Use the `Save` method to insert a new record into the database.

```php
$material = new Material([
    'Name'      => 'Nails',
    'Quantity'  => 100,
    'UnitPrice' => 0.50
]);

$mapperFactory = new MapperFactory($dbWriter, $dbReader);
$materialsMapper = $mapperFactory->GetMapper(MaterialsMapper::class);
$materialsMapper->Save($material);
```

### Reading Records

- **Get One**: Retrieve a single record by ID.
- **Get All**: Retrieve multiple records with filters and sorting.

```php
// Get a single material by ID
$material = $materialsMapper->GetOneById(1);

// Get a set of materials filtered by name
$materials = $materialsMapper->GetSet([
    new Filter('Name', '=', 'Nails')
]);
```

### Updating a Record

Modify the model’s properties and call `Save`.

```php
$material->Quantity = 200;
$materialsMapper->Save($material);
```

### Deleting a Record

Use the `Delete` method on the mapper.

```php
$materialsMapper->Delete($material);
```

## Property Mapping

The `propertyDbMap` array defines how model properties correspond to database columns. Ensure the `idProperty` and `dbTable` are correctly set in your mapper.

### Example

```php
protected array $propertyDbMap = [
    'Id'        => 'id',
    'Name'      => 'name',
    'Quantity'  => 'quantity',
    'UnitPrice' => 'unit_price'
];
```

## Notes

- **Intuitive CRUD**: Mappers determine whether to insert or update based on the `idProperty` value of the model.
- **Database-Specific Extensions**: Extend the database-specific mapper classes (`SQLite`, `Mysql`, `Postgres`) for better compatibility and performance.
- **Validation in Models**: Use validation methods in models to enforce data integrity before interacting with the mapper.
- **Separation of Concerns**: Use services to encapsulate business logic, keeping mappers focused on database interactions.

Next Topic: [Authentication](authentication.md)
