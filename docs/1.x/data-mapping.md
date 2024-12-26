# Data Mapping

Rebar provides a powerful and flexible data mapping system for connecting PHP objects (models) with database tables. This system uses mappers to handle CRUD operations and manage relationships between models and database data.

## Overview

The data mapping system in Rebar consists of:

- **Models**: Represent data entities in your application.
- **Mappers**: Provide the interface between models and the database.
- **Property Mapping**: A configuration in the mapper defining how model properties map to database columns.

## Creating a Model

Models in Rebar extend the `Fluxoft\Rebar\Model` class. Properties in models can include validation logic and track modifications for efficient updates.

### Example
```php
use Fluxoft\Rebar\Model;

class User extends Model {
	protected $properties = [
		'Id'       => ['type' => 'integer'],
		'Username' => ['type' => 'string'],
		'Email'    => ['type' => 'string'],
		'Password' => ['type' => 'string']
	];
}
```

## Creating a Mapper

Mappers in Rebar extend the `Fluxoft\Rebar\Data\Db\Mappers\GenericSql` class and define how the model properties are mapped to database columns.

### Example
```php
use Fluxoft\Rebar\Data\Db\Mappers\GenericSql;

class UserMapper extends GenericSql {
	protected array $propertyDbMap = [
		'Id'       => 'id',
		'Username' => 'username',
		'Email'    => 'email',
		'Password' => 'password'
	];
	protected string $dbTable = 'users';
}
```

## CRUD Operations

### Creating a Record

Use the `Save` method to insert a new record into the database.

```php
$user = new User([
	'Username' => 'johndoe',
	'Email'    => 'john@example.com',
	'Password' => 'securepassword'
]);

$userMapper = new UserMapper($factory, $user, $reader, $writer);
$userMapper->Save($user);
```

### Reading Records

- **Get One**: Retrieve a single record by ID or filter.
- **Get All**: Retrieve multiple records.

```php
$user = $userMapper->GetOneById(1);

$users = $userMapper->GetSet([
	new Filter('Email', '=', 'john@example.com')
]);
```

### Updating a Record

Simply modify the model’s properties and call `Save`.

```php
$user->Email = 'john.doe@example.com';
$userMapper->Save($user);
```

### Deleting a Record

Use the `DeleteById` or `DeleteOneWhere` methods.

```php
$userMapper->DeleteById(1);
```

## Property Mapping

The `propertyDbMap` defines how model properties correspond to database columns. Each property can be configured with additional options, such as types and write permissions.

### Example
```php
protected array $propertyDbMap = [
	'Id' => [
		'column' => 'id',
		'type'   => 'integer'
	],
	'Email' => [
		'column' => 'email',
		'type'   => 'string',
		'isWriteable' => true
	]
];
```

## Advanced Features

### Joins

Rebar supports SQL joins through the `joins` property in mappers. Define relationships between tables using `Fluxoft\Rebar\Data\Db\Join`.

```php
protected array $joins = [
	new Join('profile', 'INNER', 'users.id = profile.user_id')
];
```

---

Next up: the guide for [Authentication](authentication.md)!
