<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Model;
use PDO;
use PHPUnit\Framework\TestCase;

class MariaDbTest extends TestCase {
	public function testQuoteElement() {
		/** @var MapperFactory $mapperFactor */
		$mapperFactor = $this->getMockBuilder(MapperFactory::class)
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

		$mariaDb = new ConcreteMariaDb(
			$mapperFactor,
			$model,
			$pdo
		);

		$element  = 'test';
		$expected = '`test`';
		$actual   = $mariaDb->PublicQuoteElement($element);
		$this->assertEquals($expected, $actual);
	}
}

// @codingStandardsIgnoreStart
class ConcreteMariaDb extends MariaDb {
	protected array $propertyDbMap = [
		'Id'  => 'id',
		'Test' => 'test'
	];
	public function PublicQuoteElement($element) {
		return $this->QuoteElement($element);
	}
}
// @codingStandardsIgnoreEnd
