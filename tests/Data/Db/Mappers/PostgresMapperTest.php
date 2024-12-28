<?php

namespace Fluxoft\Rebar\Data\Db\Mappers;

use Fluxoft\Rebar\Data\Db\MapperFactory;
use Fluxoft\Rebar\Model;
use PDO;
use PHPUnit\Framework\TestCase;

class PostgresMapperTest extends TestCase {
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

		$postgres = new ConcretePostgres(
			$mapperFactory,
			$model,
			$pdo
		);

		$element  = 'test';
		$expected = '"test"';
		$actual   = $postgres->PublicQuoteElement($element);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider formatValueForInsertProvider
	 */
	public function testFormatValueForInsert(string $type, mixed $value, mixed $expected) {
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

		$postgres = new ConcretePostgres(
			$mapperFactory,
			$model,
			$pdo
		);

		if ($expected === 'InvalidArgumentException') {
			$this->expectException(\InvalidArgumentException::class);
			$this->expectExceptionMessage("Invalid date/time format for value '$value'");
		}

		$actual = $postgres->PublicFormatValueForInsert($type, $value);
		$this->assertEquals($expected, $actual);
	}

	public function formatValueForInsertProvider(): array {
		return [
			'Valid datetime string' => [
				'type'     => 'datetime',
				'value'    => '2024-12-31 15:30:00',
				'expected' => '2024-12-31 15:30:00'
			],
			'Valid date string' => [
				'type'     => 'date',
				'value'    => '2024-12-31',
				'expected' => '2024-12-31'
			],
			'Valid time string' => [
				'type'     => 'time',
				'value'    => '15:30:00',
				'expected' => '15:30:00'
			],
			'Invalid datetime string' => [
				'type'     => 'datetime',
				'value'    => 'invalid-date',
				'expected' => 'InvalidArgumentException'
			],
			'DateTime object' => [
				'type'     => 'datetime',
				'value'    => new \DateTime('2024-12-31 15:30:00'),
				'expected' => '2024-12-31 15:30:00'
			]
		];
	}
}

// @codingStandardsIgnoreStart
class ConcretePostgres extends PostgresMapper {
	protected array $propertyDbMap = [
		'Id'   => 'id',
		'Name' => 'name'
	];
	public function PublicQuoteElement(string $element): string {
		return $this->quoteElement($element);
	}
	public function PublicFormatValueForInsert(string $type, mixed $value): mixed {
		return $this->formatValueForInsert($type, $value);
	}
}
// @codingStandardsIgnoreEnd
