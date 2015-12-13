<?php
/**
 * Created by PhpStorm.
 * User: joehart
 * Date: 12/12/15
 * Time: 10:06 PM
 */

namespace Fluxoft\Rebar\Auth;


class BasicTest extends \PHPUnit_Framework_TestCase {
    protected function setup() {
    
    }
    
    protected function teardown() {
    
    }
    
    public function testFooNotEqualBar() {
        $this->assertNotEquals('foo','bar');
    }
}
