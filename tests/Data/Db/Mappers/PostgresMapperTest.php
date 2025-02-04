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
	public function testFormatValueForInsert(
		string $type,
		mixed $value,
		mixed $expected,
		string $exceptionMessage = null
	) {
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
			if ($exceptionMessage) {
				$this->expectExceptionMessage($exceptionMessage);
			} else {
				$this->expectExceptionMessage("Invalid date/time format for value '$value'");
			}
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
			],
			'DateTime object for date type' => [
				'type'     => 'date',
				'value'    => new \DateTime('2024-12-31 15:30:00'),
				'expected' => '2024-12-31'
			],
			'DateTime object for time type' => [
				'type'     => 'time',
				'value'    => new \DateTime('2024-12-31 15:30:00'),
				'expected' => '15:30:00'
			],
			'DateTime object for non-date/time type' => [
				'type'     => 'varchar',
				'value'    => new \DateTime('2024-12-31 15:30:00'),
				'expected' => 'InvalidArgumentException',
				'exceptionMessage' => 'Cannot format DateTime object as type: varchar'
			],
			'Invalid date string' => [
				'type'     => 'date',
				'value'    => 'invalid-date',
				'expected' => 'InvalidArgumentException'
			],
			'Invalid time string' => [
				'type'     => 'time',
				'value'    => 'invalid-time',
				'expected' => 'InvalidArgumentException'
			],
			'Non-date/time string for non-date/time type' => [
				'type'     => 'varchar',
				'value'    => 'TheQuickBrownFixJumpsOverTheLazyDog',
				'expected' => 'TheQuickBrownFixJumpsOverTheLazyDog'
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
