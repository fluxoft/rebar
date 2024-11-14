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

    protected function setup():void {
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
    protected function teardown():void {
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
            'disallowedMethod' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'POST',
                'controllerMethod' => 'Test',
                'skipAuthentication' => ['*'],
                'requireAuthentication' => [],
                'authenticationRequired' => false,
                'authUser' => false,
                'authorized' => true
            ],
            'methodAllSkippedNoneRequired' => [
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
            'methodNotSkippedAllRequired' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'GET',
                'controllerMethod' => 'Test',
                'skipAuthentication' => [],
                'requireAuthentication' => ['*'],
                'authenticationRequired' => true,
                'authUser' => false,
                'authorized' => true
            ],
            'methodNotSkippedNoneRequired' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'GET',
                'controllerMethod' => 'Test',
                'skipAuthentication' => [],
                'requireAuthentication' => [],
                'authenticationRequired' => true,
                'authUser' => false,
                'authorized' => true
            ],
            'methodNotSkippedButRequiredByNameNotAuthenticated' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'GET',
                'controllerMethod' => 'Test',
                'skipAuthentication' => [],
                'requireAuthentication' => ['Test'],
                'authenticationRequired' => true,
                'authUser' => false,
                'authorized' => true
            ],
            'methodNotSkippedButRequiredByNameAuthenticated' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'GET',
                'controllerMethod' => 'Test',
                'skipAuthentication' => [],
                'requireAuthentication' => ['Test'],
                'authenticationRequired' => true,
                'authUser' => true,
                'authorized' => true
            ],
            'methodNotSkippedButRequiredByNameDifferentMethod' => [
                'headers' => [],
                'allowedMethods' => ['GET'],
                'requestMethod' => 'GET',
                'controllerMethod' => 'Blah',
                'skipAuthentication' => [],
                'requireAuthentication' => ['Test'],
                'authenticationRequired' => false,
                'authUser' => false,
                'authorized' => true
            ]
        ];
    }

    /**
     * @covers ::Display
     */
    public function testDisplayWithNullPresenter() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenter(null);

        $this->response
            ->expects($this->once())
            ->method('Send');

        $controller->Display();
    }
    /**
     * @covers ::Display()
     */
    public function testDisplayWithDebugPresenter() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenter($this->debugPresenter);

        $this->debugPresenter
            ->expects($this->once())
            ->method('Render');

        $controller->Display();
    }
    /**
     * @covers ::Display()
     */
    public function testDisplayWithInvalidPresenter() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenter(new \stdClass());

        $this->expectException(InvalidPresenterException::class);

        $controller->Display();
    }
    /**
     * @covers ::Display()
     */
    public function testDisplayWithDebugPresenterClass() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenterClass('Debug');

        $this->response
            ->expects($this->once())
            ->method('Send');

        $controller->Display();
    }
    /**
     * @covers ::Display()
     */
    public function testDisplayWithDummyPresenterClass() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenterClass('\Fluxoft\Rebar\DummyPresenter');

        $this->response
            ->expects($this->once())
            ->method('Send');

        $controller->Display();
    }
    /**
     * @covers ::Display()
     */
    public function testDisplayWithInvalidPresenterClass() {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);
        $controller->SetPresenterClass('InvalidPresenterClassName');

        $this->expectException(InvalidPresenterException::class);

        $controller->Display();
    }

    /**
     * @dataProvider dataArrayProvider
     * @param array $dataArray
     */
    public function testSetData(array $dataArray) {
        $controller = new DummyController($this->request, $this->response, $this->webAuth);

        foreach ($dataArray as $key => $value) {
            $controller->PublicSet($key, $value);
        }

        $returnData = $controller->PublicGetData();

        $this->assertEquals($dataArray, $returnData);
    }
    public function dataArrayProvider() {
        return [
            'foobar' => [
                [
                    'foo' => 'bar'
                ]
            ],
            'multiple' => [
                [
                    'foo' => 'bar',
                    'a' => 'b'
                ]
            ],
            'objectValue' => [
                [
                    'someObject' => new \stdClass()
                ]
            ],
            'arrayValue' => [
                [
                    'arrayKey' => ['one', 'two', 'three']
                ]
            ]
        ];
    }
}

// Ignore the phpcs warning about two classes in one file
// @codingStandardsIgnoreStart
class DummyController extends Controller {
    // @codingStandardsIgnoreEnd
    protected bool $crossOriginEnabled        = true;
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
        $requiresAuth = $this->methodRequiresAuthentication($method);
        return $requiresAuth;
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

// @codingStandardsIgnoreStart
class DummyPresenter extends Debug {}
<environment_details>
# VSCode Visible Files
src/Http/Router.php

# VSCode Open Tabs
tests/Http/Middleware/CorsTest.php
src/Http/Middleware/Cors.php
src/Http/Controller.php
src/Http/Router.php
</environment_details>
