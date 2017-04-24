<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {
	protected function setup() {

	}

	protected function teardown() {

	}

	public function testAccessSimpleProperties() {
		$container = new Container();

		$container['simpleProperty1'] = 'simpleValue';
		$container->simpleProperty2   = 'simpleValue';

		$this->assertTrue(isset($container->simpleProperty1));
		$this->assertTrue(isset($container['simpleProperty2']));

		$this->assertEquals('simpleValue', $container['simpleProperty1']);
		$this->assertEquals('simpleValue', $container->simpleProperty1);
		$this->assertEquals('simpleValue', $container['simpleProperty2']);
		$this->assertEquals('simpleValue', $container->simpleProperty2);

		unset($container->simpleProperty1);
		unset($container['simpleProperty2']);

		$this->assertFalse(isset($container['simpleProperty1']));
		$this->assertFalse(isset($container->simpleProperty2));
	}

	public function testNotSetObjectAccess() {
		$container = new Container();

		$nonExistentPropertyName = 'NotExists';

		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage(sprintf('Value "%s" is not defined.', $nonExistentPropertyName));

		$fail = $container->$nonExistentPropertyName;
		unset($fail);
	}

	public function testNotSetArrayAccess() {
		$container = new Container();

		$nonExistentPropertyName = 'NotExists';

		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage(sprintf('Value "%s" is not defined.', $nonExistentPropertyName));

		$fail = $container[$nonExistentPropertyName];
		unset($fail);
	}

	public function testCallable() {
		$container = new Container();

		$container['callable'] = function () {
			return 'called';
		};

		$this->assertEquals('called', $container['callable']);
	}

	public function testCallableObjectCreatedOnce() {
		$container = new Container();

		$container['callable'] = function () {
			return new CallableObject();
		};

		$called1 = $container['callable'];
		$called2 = $container['callable'];

		$this->assertTrue($called1 === $called2);
	}

	public function testCallableFunctionCreatedOnce() {
		$container = new Container();

		$container['callable'] = function () {
			return function ($var) {
				return "the var was $var";
			};
		};

		$called1 = $container['callable'];
		$called2 = $container['callable'];

		$this->assertEquals('the var was foo', $called1('foo'));
		$this->assertEquals('the var was bar', $called2('bar'));

		$this->assertTrue($called1 === $called2);
	}
}

// @codingStandardsIgnoreStart
class CallableObject {
	// @codingStandardsIgnoreEnd

	public function __construct() {
		//var_dump("***** I got called *****");
	}
}
