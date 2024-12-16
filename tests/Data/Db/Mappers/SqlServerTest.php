<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Model;
use PDO;
use PHPUnit\Framework\TestCase;

class SqlServerTest extends TestCase {
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

		$sqlServer = new ConcreteSqlServer(
			$mapperFactory,
			$model,
			$pdo
		);

		$element  = 'test';
		$expected = '[test]';
		$actual   = $sqlServer->PublicQuoteElement($element);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider applyPaginationProvider
	 */
	public function testApplyPagination(string $sql, int $page, int $pageSize, string $expected) {
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

		$sqlServer = new ConcreteSqlServer(
			$mapperFactory,
			$model,
			$pdo
		);

		$actual = $sqlServer->PublicApplyPagination($sql, $page, $pageSize);
		$this->assertEquals($expected, $actual);
	}

	public function applyPaginationProvider(): array {
		return [
			'No pagination needed' => [
				'sql'      => 'SELECT * FROM Users',
				'page'     => 1,
				'pageSize' => 0,
				'expected' => 'SELECT * FROM Users'
			],
			'Pagination with ORDER BY' => [
				'sql'      => 'SELECT * FROM Users ORDER BY Id',
				'page'     => 2,
				'pageSize' => 10,
				'expected' => 'SELECT * FROM Users ORDER BY Id OFFSET 10 ROWS FETCH NEXT 10 ROWS ONLY'
			],
			'Pagination without ORDER BY' => [
				'sql'      => 'SELECT * FROM Users',
				'page'     => 1,
				'pageSize' => 10,
				'expected' => 'SELECT * FROM Users ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY'
			]
		];
	}
}

// @codingStandardsIgnoreStart
class ConcreteSqlServer extends SqlServer {
	protected array $propertyDbMap = [
		'Id'   => 'id',
		'Name' => 'name'
	];
	public function PublicQuoteElement(string $element): string {
		return $this->quoteElement($element);
	}
	public function PublicApplyPagination(string $sql, int $page, int $pageSize): string {
		return $this->applyPagination($sql, $page, $pageSize);
	}
}
// @codingStandardsIgnoreEnd
