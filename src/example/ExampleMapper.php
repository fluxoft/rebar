<?php

namespace Example;

use Fluxoft\Rebar\Db\Filter;
use Fluxoft\Rebar\Db\Mappers\Postgres;
use Fluxoft\Rebar\Db\Join;
use Fluxoft\Rebar\Db\Property;
use Fluxoft\Rebar\Db\MapperFactory;
use Fluxoft\Rebar\Model;

class ExampleMapper extends Postgres {
	public function __construct(MapperFactory $mapperFactory, Model $model, \PDO $dbConnection) {
		parent::__construct($mapperFactory, $model, $dbConnection);
		$this->dbTable       = 'example';
		$this->idProperty    = 'Id';
		$this->propertyDbMap = [
			'Id' => new Property('id', 'integer'),
			'Name' => new Property('name', 'string'),
			'CreatedOn' => new Property('created_on', 'datetime'),
			'TotalLogins' => new Property('COUNT("logins".*)', 'integer')
		];
		// $this->selectSql = '
		// 	SELECT
		// 		"example"."id" AS "Id",
		// 		"example"."name" AS "Name",
		// 		"example"."created_on" AS "CreatedOn",
		// 		(SELECT COUNT(*) FROM logins WHERE user_id = "example"."id") AS "TotalLogins"
		// 	FROM example
		// ';
		$this->joins = [
			'logins' => new Join('LEFT', 'logins', 'logins.user_id = example.id')
		];
	}
}

// @codingStandardsIgnoreStart
class Example extends Model {
	protected $properties = [
		'Id' => null,
		'Name' => null,
		'CreatedOn' => null,
		'TotalLogins' => 0
	];
}
// $mapperFactoruy = new MapperFactory();
// $dbConnection   = new \PDO('pgsql:host=localhost;dbname=example', 'user', 'password');
// $exampleMapper  = new ExampleMapper($mapperFactory, new Example(), $dbConnection);

// $usersWithMoreThan10Logins = $exampleMapper->GetSet([new Filter('TotalLogins', '>', 10)]);
// @codingStandardsIgnoreEnd
