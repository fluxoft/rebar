<?php

namespace Fluxoft\Rebar\Data\Db;

use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;
use Fluxoft\Rebar\Data\FilterInterface;
use Fluxoft\Rebar\Data\SortInterface;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractServiceTest extends TestCase {
	private ConcreteService $service;
	/** @var MockObject|MapperInterface */
	private MockObject $mapperMock;

	protected function setUp(): void {
		/** @var MockObject|MapperInterface */
		$this->mapperMock = $this->createMock(MapperInterface::class);
		$this->service    = new ConcreteService($this->mapperMock);
	}

	public function testFetch() {
		$modelMock = $this->createMock(Model::class);
		$this->mapperMock->expects($this->once())
			->method('GetOneById')
			->with(1)
			->willReturn($modelMock);

		$result = $this->service->Fetch(1);
		$this->assertSame($modelMock, $result);
	}

	public function testFetchThrowsExceptionForInvalidId() {
		$id = 999; // Simulate an ID that does not exist
	
		$this->mapperMock->expects($this->once())
			->method('GetOneById')
			->with($id)
			->willReturn(null); // Simulate not finding the model
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("Model with ID $id not found.");
	
		$this->service->Fetch($id);
	}

	public function testFetchAll() {
		$filters = ['name' => 'John Doe', 'age[gte]' => 30]; // Example filters
		$sort    = ['-createdAt', 'name']; // Example sorting fields
		$models  = [$this->createMock(Model::class), $this->createMock(Model::class)];
	
		$this->mapperMock->expects($this->once())
		->method('GetSet')
		->with(
			$this->callback(function ($actualFilters) use ($filters) {
				// Verify that the filters were correctly processed
				return is_array($actualFilters) && count($actualFilters) === count($filters);
			}),
			$this->callback(function ($actualSort) use ($sort) {
				// Verify that sort was correctly processed
				return is_array($actualSort) && count($actualSort) === count($sort);
			}),
			2, 10
		)
		->willReturn($models);
	
		$result = $this->service->FetchAll($filters, $sort, 2, 10);
		$this->assertSame($models, $result);
	}

	public function testCountAll() {
		$filters = [$this->createMock(FilterInterface::class)];
		$this->mapperMock->expects($this->once())
			->method('Count')
			->with($filters)
			->willReturn(42);

		$result = $this->service->CountAll($filters);
		$this->assertSame(42, $result);
	}

	public function testCreate() {
		$data      = ['Id' => 1, 'Name' => 'New Item']; // Ensure keys match properties of the Model
		$modelMock = $this->getMockBuilder(Model::class)
			->setConstructorArgs([$data])
			->getMock();
	
		$modelMock->method('HasProperty')->willReturnCallback(function ($key) use ($data) {
			return array_key_exists($key, $data);
		});
	
		$this->mapperMock->expects($this->once())
			->method('GetNew')
			->willReturn($modelMock);
	
		$this->mapperMock->expects($this->once())
			->method('Save')
			->with($modelMock);
	
		$result = $this->service->Create($data);
		$this->assertSame($modelMock, $result);
	}

	public function testUpdate() {
		$id        = 1;
		$data      = ['Id' => 1, 'Name' => 'Updated Item']; // Ensure keys match properties of the Model
		$modelMock = $this->getMockBuilder(Model::class)
			->setConstructorArgs([['Id' => $id]])
			->getMock();
	
		$modelMock->method('HasProperty')->willReturnCallback(function ($key) use ($data) {
			return array_key_exists($key, $data);
		});
	
		$this->mapperMock->expects($this->once())
			->method('GetOneById')
			->with($id)
			->willReturn($modelMock);
	
		foreach ($data as $key => $value) {
			$modelMock->$key = $value; // Direct assignment for testing
		}
	
		$this->mapperMock->expects($this->once())
			->method('Save')
			->with($modelMock);
	
		$result = $this->service->Update($id, $data);
		$this->assertSame($modelMock, $result);
	}

	public function testDelete() {
		$id        = 1;
		$modelMock = $this->createMock(Model::class);

		$this->mapperMock->expects($this->once())
			->method('GetOneById')
			->with($id)
			->willReturn($modelMock);

		$this->mapperMock->expects($this->once())
			->method('Delete')
			->with($modelMock);

		$this->service->Delete($id);
	}

	public function testBuildFiltersWithComplexCriteria() {
		$rawFilters = [
			'name' => 'John Doe',
			'age' => ['gte' => 30],
			'price' => ['between' => '10|50'],
			'status' => ['in' => 'active|pending'],
			'isAdmin' => ['isnull' => null]
		];
	
		$expectedFilters = [
			new Filter('name', '=', 'John Doe'),
			new Filter('age', '>=', 30),
			new Filter('price', 'BETWEEN', ['10', '50']),
			new Filter('status', 'IN', ['active', 'pending']),
			new Filter('isAdmin', 'IS', null)
		];
	
		$result = $this->service->ExposeBuildFilters($rawFilters);
	
		$this->assertEquals($expectedFilters, $result);
	}

	public function testBuildFiltersWithInvalidOperator() {
		$rawFilters = ['name' => ['invalid' => 'John Doe']];
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("Invalid operator 'invalid' for property 'name'.");
	
		$this->service->ExposeBuildFilters($rawFilters);
	}

	public function testBuildFilteresWithInvalidBetweenValue() {
		$rawFilters = ['price' => ['between' => '10']];
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('BETWEEN operator requires two values separated by a pipe.');
	
		$this->service->ExposeBuildFilters($rawFilters);
	}
}

/**
 * A concrete implementation of AbstractService for testing purposes.
 */
// @codingStandardsIgnoreStart
class ConcreteService extends AbstractService {
	public function ExposeBuildFilters(array $rawFilters): array {
		$filters = $this->buildFilters($rawFilters);
		return $filters;
	}
}
// @codingStandardsIgnoreEnd
