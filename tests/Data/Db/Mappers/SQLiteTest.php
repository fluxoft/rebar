<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Model;
use PDO;
use PHPUnit\Framework\TestCase;

class SQLiteTest extends TestCase {
	public function testQuoteElement() {
		/** @var MapperFactory $mapperFactory */
		$mapperFactory = $this->getMockBuilder(MapperFactory::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var Model $model */
		$model = $this->getMockBuilder(Model::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var PDO $pdo */
		$pdo = $this->getMockBuilder(PDO::class)
			->disableOriginalConstructor()
			->getMock();

		$sqlite = new ConcreteSQLite(
			$mapperFactory,
			$model,
			$pdo
		);

		$element  = 'test';
		$expected = '"test"';
		$actual   = $sqlite->PublicQuoteElement($element);
		$this->assertEquals($expected, $actual);
	}
}

// @codingStandardsIgnoreStart
class ConcreteSQLite extends SQLite {
	protected array $propertyDbMap = [
		'Id'   => 'id',
		'Name' => 'name'
	];
	public function PublicQuoteElement(string $element): string {
		return $this->quoteElement($element);
	}
}
// @codingStandardsIgnoreEnd
