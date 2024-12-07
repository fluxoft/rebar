<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Container;
use Fluxoft\Rebar\Data\ServiceInterface;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Model;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractRestControllerTest extends TestCase {
	/** @var MockObject|ServiceInterface */
	private MockObject $serviceMock;
	/** @var MockObject|Request */
	private MockObject $requestMock;
	/** @var Response */
	private Response $responseMock;

	private ConcreteRestController $controller;

	protected function setUp(): void {
		/** @var MockObject|ServiceInterface */
		$this->serviceMock = $this->createMock(ServiceInterface::class);
		/** @var MockObject|Request */
		$this->requestMock = $this->createMock(Request::class);
		// use a real Response object so we can check the status
		$this->responseMock = new Response();

		$this->controller = new ConcreteRestController($this->requestMock, $this->responseMock, $this->serviceMock);

		// Initialize the controller's service
		/** @var MockObject|Container */
		$container = $this->createMock(Container::class);
		$container->method('offsetGet')
			->willReturn($this->serviceMock);
		$this->controller->Setup($container);
	}

	public function testGetWithId(): void {
		$id        = 123;
		$modelMock = $this->createMock(Model::class);

		$this->serviceMock->expects($this->once())
			->method('Fetch')
			->with($id)
			->willReturn($modelMock);

		$this->requestMock
			->method('__get')
			->with('Method')
			->willReturn('GET');

		$this->controller->Default($id);

		$this->assertEquals(200, $this->responseMock->Status);
		$this->assertArrayHasKey('data', $this->controller->GetData());
		$this->assertSame($modelMock, $this->controller->GetData()['data']);
	}

	public function testGetAll(): void {
		$models = [new \stdClass(), new \stdClass()];

		$this->serviceMock->expects($this->once())
			->method('FetchAll')
			->with([], [], 1, 20) // Default filters, sort, page, and pageSize
			->willReturn($models);

		$this->serviceMock->expects($this->once())
			->method('CountAll')
			->with([])
			->willReturn(count($models));

		$getParamsMock = $this->createMock(ParameterSet::class);

		$getParamsMock->expects($this->exactly(5))
			->method('Get')
			->willReturnMap([
				['filter', [], []], // Ensures `filter` returns an empty array by default
				['sort', [], []],   // Ensures `sort` returns an empty array by default
				['page', 1, 1],     // Default page number
				['pageSize', 20, 20] // Default page size
			]);

		$this->requestMock
			->method('__get')
			->willReturnMap([
				['Get', $getParamsMock],
				['Method', 'GET']
			]);

		$this->controller->Default();

		$this->assertEquals(200, $this->responseMock->Status);
		$this->assertArrayHasKey('data', $this->controller->GetData());
		$this->assertSame($models, $this->controller->GetData()['data']);
	}

	public function testPost(): void {
		$modelMock      = $this->createMock(Model::class);
		$data           = ['name' => 'New Item'];
		$postParamsMock = $this->createMock(ParameterSet::class);
	
		$postParamsMock->expects($this->once())
			->method('Get')
			->willReturn($data);
	
		$this->serviceMock->expects($this->once())
			->method('Create')
			->with($data)
			->willReturn($modelMock);
	
		$this->requestMock->method('__get')
			->willReturnMap([
				['Post', $postParamsMock],
				['Method', 'POST']
			]);
	
		$this->controller->Default();
	
		$this->assertEquals(201, $this->responseMock->Status);
		$this->assertArrayHasKey('data', $this->controller->GetData());
		$this->assertSame($modelMock, $this->controller->GetData()['data']);
	}
	

	public function testPut(): void {
		$id             = 1;
		$data           = ['name' => 'Updated Item'];
		$modelMock      = $this->createMock(Model::class);
		$postParamsMock = $this->createMock(ParameterSet::class);
	
		$postParamsMock->expects($this->once())
			->method('Get')
			->willReturn($data);
	
		$this->serviceMock->expects($this->once())
			->method('Update')
			->with($id, $data)
			->willReturn($modelMock);
	
		$this->requestMock->method('__get')
			->willReturnMap([
				['Post', $postParamsMock],
				['Method', 'PUT']
			]);
	
		$this->controller->Default($id);
	
		$this->assertEquals(200, $this->responseMock->Status);
		$this->assertArrayHasKey('data', $this->controller->GetData());
		$this->assertSame($modelMock, $this->controller->GetData()['data']);
	}

	public function testDelete(): void {
		$id = 1;
	
		$this->serviceMock->expects($this->once())
			->method('Delete')
			->with($id);
	
		$this->requestMock->method('__get')
			->willReturnMap([
				['Method', 'DELETE']
			]);
	
		$this->controller->Default($id);
	
		$this->assertEquals(204, $this->responseMock->Status);
		$this->assertArrayNotHasKey('data', $this->controller->GetData());
	}

	public function testPutWithoutId(): void {
		$this->requestMock->method('__get')
			->willReturnMap([
				['Method', 'PUT'],
			]);
	
		$this->controller->Default(null);
	
		$this->assertEquals(400, $this->responseMock->Status);
		$this->assertArrayHasKey('error', $this->controller->GetData());
		$this->assertEquals('ID parameter is required for PUT.', $this->controller->GetData()['error']);
	}
	
	public function testDeleteWithoutId(): void {
		$this->requestMock->method('__get')
			->willReturnMap([
				['Method', 'DELETE'],
			]);
	
		$this->controller->Default(null);
	
		$this->assertEquals(400, $this->responseMock->Status);
		$this->assertArrayHasKey('error', $this->controller->GetData());
		$this->assertEquals('ID parameter is required for DELETE.', $this->controller->GetData()['error']);
	}

	public function testGetInvalidArgument(): void {
		$this->serviceMock->expects($this->once())
			->method('Fetch')
			->willThrowException(new \InvalidArgumentException('Invalid ID.'));
	
		$this->requestMock->method('__get')
			->willReturnMap([
				['Method', 'GET'],
			]);
	
		$this->controller->Default(999);
	
		$this->assertEquals(400, $this->responseMock->Status);
		$this->assertArrayHasKey('error', $this->controller->GetData());
		$this->assertEquals('Invalid ID.', $this->controller->GetData()['error']);
	}
}

/**
 * Concrete implementation of AbstractRestController for testing purposes.
 */
// @codingStandardsIgnoreStart
class ConcreteRestController extends AbstractRestController {
	protected function GetServiceClass(): string {
		return MockService::class;
	}
	// expose the controller's $data property for testing
	public function GetData(): array {
		return $this->data;
	}
}
class MockService implements ServiceInterface {
	public function Fetch(mixed $id): Model {
		return new Model(); // Replace with a mock implementation as needed
	}
	public function FetchAll(array $filters = [], array $sort = [], int $page = 1, int $pageSize = 20): array {
		return [];
	}
	public function CountAll(array $filters = []): int {
		return 0;
	}
	public function Create(array $data): Model {
		return new Model();
	}
	public function Update(mixed $id, array $data): Model {
		return new Model();
	}
	public function Delete(mixed $id): void {
		// No-op
	}
}
// @codingStandardsIgnoreEnd
