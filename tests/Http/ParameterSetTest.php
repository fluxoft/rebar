<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 12/12/15
 * Time: 10:10 PM
 */

namespace Fluxoft\Rebar\Http;


class ParameterSetTest extends \PHPUnit_Framework_TestCase {
    protected function setup() {
    
    }
    
    protected function teardown() {
    
    }
    
    public function testFooNotEqualBar() {
        $this->assertNotEquals('foo','bar');
    }
}
