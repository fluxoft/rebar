<?php

namespace Fluxoft\Rebar;

use PHPUnit\Framework\TestCase;

class ContainerDefinitionTest extends TestCase {
	public function testCanSetAndRetrieveProperties(): void {
		$class        = 'PDO';
		$dependencies = ['DbReaderConnectionString'];

		$definition = new ContainerDefinition($class, $dependencies);

		$this->assertEquals($class, $definition->Class);
		$this->assertEquals($dependencies, $definition->Dependencies);
	}

	public function testThrowsExceptionForNonExistentClass(): void {
		$nonExistentClass = 'NonExistentClass';

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("Class '$nonExistentClass' does not exist.");

		new ContainerDefinition($nonExistentClass);
	}

	public function testThrowsExceptionForNonStringClass(): void {
		$nonStringDependencyName = 12345;

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'Dependency for class \'PDO\' must be a string key. Found: ' . gettype($nonStringDependencyName)
		);

		new ContainerDefinition('PDO', [$nonStringDependencyName]);
	}
}
