<?php

namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\Reply;
use Fluxoft\Rebar\Auth\Web;
use Fluxoft\Rebar\Http\Controller;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Presenters\Debug;
use Fluxoft\Rebar\Presenters\Exceptions\InvalidPresenterException;
use PHPUnit\Framework\TestCase;

/**
 * Class ControllerTest
 * @package Fluxoft\Rebar
 * @coversDefaultClass \Fluxoft\Rebar\Http\Controller
 */
class ControllerTest extends TestCase {
	/** @var Request */
	protected $request;
	/** @var Response */
	protected $response;
	/** @var Web */
	protected $webAuth;
	/** @var Debug */
	protected $debugPresenter;

	protected $controller;

 protected function setUp(): void {
	 $this->request        = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->response       = $this->getMockBuilder('\Fluxoft\Rebar\Http\Response')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->webAuth        = $this->getMockBuilder('\Fluxoft\Rebar\Auth\Web')
		 ->disableOriginalConstructor()
		 ->getMock();
	 $this->debugPresenter = $this->getMockBuilder('\Fluxoft\Rebar\Presenters\Debug')
		 ->disableOriginalConstructor()
		 ->getMock();
 }

 protected function tearDown(): void {
	 unset($this->debugPresenter);
	 unset($this->webAuth);
	 unset($this->request);
	 unset($this->response);
 }

	/**
	 * @dataProvider authorizeProvider
	 * @covers ::Authorize()
	 * @covers ::methodRequiresAuthentication()
	 * @param array $headers
	 * @param array $allowedMethods
	 * @param string $requestMethod
	 * @param string $controllerMethod
	 * @param array $skipAuthentication
	 * @param array $requireAuthentication
	 * @param bool $authenticationRequired
	 * @param bool $authUser
	 * @param bool $authorized
	 */
 public function testAuthorize(
		array $headers,
		array $allowedMethods,
		string $requestMethod,
		string $controllerMethod,
		array $skipAuthentication,
		array $requireAuthentication,
		bool $authenticationRequired,
		bool $authUser,
		bool $authorized
	) {
	 $controller = new DummyController($this->request, $this->response, $this->webAuth);
	 $controller->SetSkipAuthentication($skipAuthentication);
	 $controller->SetRequireAuthentication($requireAuthentication);

	 $authReply       = new Reply();
	 $authReply->Auth = $authUser;
	 $this->webAuth
		 ->expects($this->any())
		 ->method('GetAuthenticatedUser')
		 ->will($this->returnValue($authReply));

  if (!empty($allowedMethods)) {
	  $controller->SetAllowedMethods($allowedMethods);
  }
	 $this->request
		 ->expects($this->any())
		 ->method('__get')
		 ->willReturnMap([
			 ['Headers', $headers],
			 ['Method', $requestMethod]
		 ]);

  if (!in_array($requestMethod, $allowedMethods)) {
	  $this->expectException('\Fluxoft\Rebar\Exceptions\MethodNotAllowedException');
  }
	 $this->assertEquals(
		 $authenticationRequired,
		 $controller->PublicMethodRequiresAuthentication($controllerMethod)
	 );
  if ($authenticationRequired && !$authUser) {
	  $this->expectException('\Fluxoft\Rebar\Auth\Exceptions\AccessDeniedException');
  }

	 $this->assertEquals($authorized, $controller->Authorize($controllerMethod));
 }

 public function authorizeProvider() {
	 return [
		 'optionsAllow' => [
			 'headers' => [],
			 'allowedMethods' => ['GET'],
			 'requestMethod' => 'GET',
			 'controllerMethod' => 'Test',
			 'skipAuthentication' => ['*'],
			 'requireAuthentication' => [],
			 'authenticationRequired' => false,
			 'authUser' => false,
			 'authorized' => true
		 ],
		 // Additional test cases...
	 ];
 }

	// Other test methods...

}

// @codingStandardsIgnoreStart
// DummyController class definition
class DummyController extends Controller {
 protected bool $crossOriginEnabled   = true;
	protected $crossOriginDomainsAllowed = [
		'http://test.com'
	];

	public function SetAllowedMethods(array $allowedMethods) {
		$this->allowedMethods = $allowedMethods;
	}
	public function GetCrossOriginDomainsAllowed() {
		return $this->crossOriginDomainsAllowed;
	}
	public function SetSkipAuthentication(array $skipAuthentication) {
		$this->skipAuthentication = $skipAuthentication;
	}
	public function SetRequireAuthentication(array $requireAuthentication) {
		$this->requireAuthentication = $requireAuthentication;
	}
	public function PublicMethodRequiresAuthentication($method) {
		return $this->methodRequiresAuthentication($method);
	}
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
}
// @codingStandardsIgnoreEnd
