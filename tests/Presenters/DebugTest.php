<?php

namespace Fluxoft\Rebar\Presenters;


class DebugTest extends \PHPUnit_Framework_TestCase {
    protected function setup() {
    
    }
    
    protected function teardown() {
    
    }
    
    public function testFooNotEqualBar() {
        $this->assertNotEquals('foo','bar');
    }
}
