<?php

namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Http\Presenters\DebugPresenter;
use Fluxoft\Rebar\Http\Presenters\Exceptions\InvalidPresenterException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Fluxoft\Rebar\Http\Controller
 */
class ControllerTest extends TestCase {
	private $request;
	private $response;

	protected function setUp(): void {
		$this->request  = $this->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->getMock();
		$this->response = $this->getMockBuilder(Response::class)
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		unset($this->request);
		unset($this->response);
	}

	/**
	 * @covers ::Display
	 * @covers ::initializePresenter
	 */
	public function testDisplayWithDefaultPresenter(): void {
		$controller     = new DummyController($this->request, $this->response);
		$debugPresenter = $this->createMock(DebugPresenter::class);

		// Mock the Debug presenter behavior
		$debugPresenter->expects($this->once())
			->method('Render')
			->with($this->response, []);

		$controller->SetPresenter($debugPresenter);
		$controller->Display();
	}

	/**
	 * @covers ::Display
	 * @covers ::initializePresenter
	 */
	public function testDisplayWithInvalidPresenterThrowsException(): void {
		$controller = new DummyController($this->request, $this->response);
		$controller->SetPresenterClass(InvalidPresenter::class);

		$this->expectException(InvalidPresenterException::class);
		$this->expectExceptionMessage('Presenter must implement PresenterInterface.');

		$controller->Display();
	}

	/**
	 * @covers ::set
	 * @covers ::getData
	 */
	public function testSetAndGetData(): void {
		$controller = new DummyController($this->request, $this->response);

		$controller->PublicSet('key1', 'value1');
		$controller->PublicSet('key2', 'value2');

		$data = $controller->PublicGetData();
		$this->assertArrayHasKey('key1', $data);
		$this->assertEquals('value1', $data['key1']);
		$this->assertArrayHasKey('key2', $data);
		$this->assertEquals('value2', $data['key2']);
	}

	/**
	 * @covers ::initializePresenter
	 */
	public function testInitializePresenterCreatesDebugPresenter(): void {
		$controller = new DummyController($this->request, $this->response);

		$presenter = $controller->PublicInitializePresenter();
		$this->assertInstanceOf(DebugPresenter::class, $presenter);
	}

	/**
	 * @covers ::initializePresenter
	 */
	public function testInitializePresenterWithValidPresenterClass(): void {
		$controller = new DummyController($this->request, $this->response);
		$controller->SetPresenterClass(DebugPresenter::class);

		$presenter = $controller->PublicInitializePresenter();
		$this->assertInstanceOf(DebugPresenter::class, $presenter);
	}

	/**
	 * @covers ::initializePresenter
	 */
	public function testInitializePresenterWithInvalidPresenterClassThrowsException(): void {
		$controller = new DummyController($this->request, $this->response);
		$controller->SetPresenterClass(InvalidPresenter::class);

		$this->expectException(InvalidPresenterException::class);
		$this->expectExceptionMessage('Presenter must implement PresenterInterface.');

		$controller->PublicInitializePresenter();
	}

	public function testManuallySettingInvalidPresenterThrowsTypeError(): void {
		$this->expectException(\TypeError::class);

		$controller = new DummyController($this->request, $this->response);
		$controller->SetPresenter(new InvalidPresenter()); // Triggers TypeError
	}
}

// @codingStandardsIgnoreStart
// DummyController class definition
class DummyController extends Controller {
	public function SetPresenter($presenter) {
		$this->presenter = $presenter;
	}
	public function SetPresenterClass($presenterClass) {
		$this->presenterClass = $presenterClass;
	}
	public function PublicSet($key, $value) {
		$this->set($key, $value);
	}
	public function PublicGetData() {
		return $this->getData();
	}
	public function PublicInitializePresenter() {
		return $this->initializePresenter();
	}
}
class InvalidPresenter {}
// @codingStandardsIgnoreEnd
