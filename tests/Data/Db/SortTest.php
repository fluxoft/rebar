<?php

namespace Fluxoft\Rebar\Data\Db;

use PHPUnit\Framework\TestCase;

class SortTest extends TestCase {
	public function testValidSortConstruction() {
		$sort = new Sort('PropertyName', 'asc');
		$this->assertSame('PropertyName', $sort->GetProperty());
		$this->assertSame('ASC', $sort->GetDirection());

		$sort = new Sort('PropertyName', 'DESC');
		$this->assertSame('PropertyName', $sort->GetProperty());
		$this->assertSame('DESC', $sort->GetDirection());
	}

	public function testInvalidDirectionThrowsException() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Direction must be either "ASC" or "DESC".');

		new Sort('PropertyName', 'INVALID');
	}

	public function testSortCreateMethod() {
		$sort = Sort::Create('PropertyName', 'asc');
		$this->assertInstanceOf(Sort::class, $sort);
		$this->assertSame('PropertyName', $sort->GetProperty());
		$this->assertSame('ASC', $sort->GetDirection());
	}
}
