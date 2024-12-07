<?php

namespace Fluxoft\Rebar\Auth;

use Fluxoft\Rebar\Auth\TokenManager;
use Fluxoft\Rebar\Auth\UserInterface;
use Fluxoft\Rebar\Auth\UserMapperInterface;
use Fluxoft\Rebar\Http\ParameterSet;
use Fluxoft\Rebar\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

trait WebAuthTestSetup {
	/** @var UserMapperInterface|MockObject */
	private $userMapperObserver;
	/** @var TokenManager|MockObject */
	private $tokenManagerObserver;
	/** @var UserInterface|MockObject */
	private $userObserver;
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var ParameterSet|MockObject */
	private $headersParamSet;
	/** @var ParameterSet|MockObject */
	private $getParamSet;
	/** @var ParameterSet|MockObject */
	private $cookiesParamSet;
	/** @var ParameterSet|MockObject */
	private $sessionParamSet;

	protected function setUp(): void {
		$this->userMapperObserver   = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserMapperInterface')
			->getMock();
		$this->userObserver         = $this->getMockBuilder('\Fluxoft\Rebar\Auth\UserInterface')
			->getMock();
		$this->headersParamSet      = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
			->getMock();
		$this->getParamSet          = $this->getMockBuilder('\Fluxoft\Rebar\Http\ParameterSet')
			->disableOriginalConstructor()
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
				if ($key === 'Headers') {
					return $this->headersParamSet;
				}
				if ($key === 'Get') {
					return $this->getParamSet;
				}
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
			$this->requestObserver,
			$this->headersParamSet
		);
	}
}
