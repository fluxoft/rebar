<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\TokenManager;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Http\Cookies;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Session;
use PHPUnit\Framework\MockObject\MockObject;

trait WebAuthTestSetup {
	/** @var UserMapperInterface|MockObject */
	private $userMapperObserver;
	/** @var TokenManager|MockObject */
	private $tokenManagerObserver;
	/** @var UserInterface|MockObject */
	private $userObserver;
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var Cookies|MockObject */
	private $cookiesParamSet;
	/** @var Session|MockObject */
	private $sessionParamSet;

	protected function setUp(): void {
		$this->userMapperObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver         = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->cookiesParamSet      = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
			->getMock();
		$this->sessionParamSet      = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenManagerObserver = $this->getMockBuilder('\Fluxoft\Rebar\Auth\TokenManager')
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver      = $this->getMockBuilder('\Fluxoft\Rebar\Http\Request')
			->disableOriginalConstructor()
			->getMock();

		// Mock the Request::__get method to return the Headers, Cookies, and Session parameter sets
		$this->requestObserver
			->method('__get')
			->willReturnCallback(function ($key) {
				if ($key === 'Cookies') {
					return $this->cookiesParamSet;
				}
				if ($key === 'Session') {
					return $this->sessionParamSet;
				}
				return null;
			});
	}

	protected function tearDown(): void {
		unset(
			$this->userMapperObserver,
			$this->tokenManagerObserver,
			$this->userObserver,
			$this->requestObserver
		);
	}
}
