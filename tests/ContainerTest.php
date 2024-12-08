<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {
	protected function setup():void {}

	protected function teardown():void {}

	public function testPsr11() {
		$container = new Container();

		$container['simpleValue']    = 'simpleValue';
		$container['simpleCallable'] = function () {
			return 'simpleCallable';
		};

		$this->assertTrue($container->has('simpleValue'));
		$this->assertTrue($container->has('simpleCallable'));

		$this->assertEquals('simpleValue', $container->get('simpleValue'));
		$this->assertEquals('simpleCallable', $container->get('simpleCallable'));
	}

	public function testPsr11NotFound() {
		$container = new Container();

		$this->assertFalse($container->has('notFound'));

		$this->expectException('Fluxoft\Rebar\Exceptions\NotFoundException');
		$this->expectExceptionMessage('Value "notFound" is not defined.');

		$container->get('notFound');
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

	public function testLoadDefinitionsWithContainerDefinition() {
		$container = new Container();
	
		$definitions = [
			'DbReaderConnectionString' => 'sqlite::memory:',
			'DbReader' => new ContainerDefinition(\PDO::class, ['DbReaderConnectionString']),
		];
	
		$container->LoadDefinitions($definitions);
	
		// Verify the scalar value
		$this->assertEquals('sqlite::memory:', $container['DbReaderConnectionString']);
		// Verify the object
		$this->assertInstanceOf(\PDO::class, $container['DbReader']);
	}
	
	public function testLoadDefinitionsWithInvalidDefinitionType() {
		$container = new Container();
	
		$definitions = [
			'InvalidType' => new \stdClass(), // Invalid definition
		];
	
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			"Invalid definition for key 'InvalidType'. Expected a ContainerDefinition, alias string, or scalar."
		);
	
		$container->LoadDefinitions($definitions);
	}
	
	
	public function testLoadDefinitionsThrowsOnNonExistentDependency() {
		$container = new Container();
	
		$definitions = [
			'WidgetService' => new ContainerDefinition(WidgetService::class, ['NonExistentDependency']),
		];
	
		$container->LoadDefinitions($definitions);
	
		$this->expectException(\Fluxoft\Rebar\Exceptions\NotFoundException::class);
		$this->expectExceptionMessage('Value "NonExistentDependency" is not defined.');
	
		$container['WidgetService'];
	}
	
	public function testLoadDefinitionsWithComplexDependencyGraph() {
		$container = new Container();
	
		$definitions = [
			'DbReaderConnectionString' => 'sqlite::memory:',
			'DbReader' => new ContainerDefinition(\PDO::class, ['DbReaderConnectionString']),
			'WidgetMapper' => new ContainerDefinition(WidgetMapper::class, ['DbReader']),
			'WidgetService' => new ContainerDefinition(WidgetService::class, ['WidgetMapper']),
		];
	
		$container->LoadDefinitions($definitions);
	
		$widgetService = $container['WidgetService'];
		$this->assertInstanceOf(WidgetService::class, $widgetService);
	
		$widgetMapper = $container['WidgetMapper'];
		$this->assertInstanceOf(WidgetMapper::class, $widgetMapper);
	
		$dbReader = $container['DbReader'];
		$this->assertInstanceOf(\PDO::class, $dbReader);
	}

	public function testLoadDefinitionsWithAlias() {
		$container = new Container();
	
		$definitions = [
			'ActualKey' => 'ActualValue',
			'Alias' => 'ActualKey',
		];
	
		$container->LoadDefinitions($definitions);
	
		$this->assertTrue($container->has('Alias'));
		$this->assertEquals('ActualValue', $container->get('Alias'));
		$this->assertEquals('ActualValue', $container->get('ActualKey'));
	}

	public function testLoadDefinitionsWithAliasReverseOrder() {
		$container = new Container();
	
		$definitions = [
			'Alias' => 'ActualKey',
			'ActualKey' => 'ActualValue',
		];
	
		$container->LoadDefinitions($definitions);
	
		$this->assertTrue($container->has('Alias'));
		$this->assertEquals('ActualValue', $container->get('Alias'));
		$this->assertEquals('ActualValue', $container->get('ActualKey'));
	}
}

// @codingStandardsIgnoreStart
class WidgetMapper {
    public function __construct(\PDO $dbReader) {
        // Constructor logic for testing purposes
    }
}

class WidgetService {
    public function __construct(WidgetMapper $mapper) {
        // Constructor logic for testing purposes
    }
}
class CallableObject {
	// @codingStandardsIgnoreEnd

	public function __construct() {
		//var_dump("***** I got called *****");
	}
}
// @codingStandardsIgnoreEnd
