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
		$filters = [$this->createMock(FilterInterface::class)];
		$sort    = [$this->createMock(SortInterface::class)];
		$models  = [$this->createMock(Model::class), $this->createMock(Model::class)];

		$this->mapperMock->expects($this->once())
			->method('GetSet')
			->with($filters, $sort, 2, 10)
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
}

/**
 * A concrete implementation of AbstractService for testing purposes.
 */
// @codingStandardsIgnoreStart
class ConcreteService extends AbstractService {}
// @codingStandardsIgnoreEnd
