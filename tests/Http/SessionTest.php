<?php

namespace Fluxoft\Rebar\Http;

use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase {
	/**
	 * @param array $sessionSet
	 * @dataProvider sessionProvider
	 */
 public function test($sessionSet = []) {
	 $session = new MockableSession();

	 $session->SetSessionParams($sessionSet);

	 // test getting entire array with no parameters to Get
	 $this->assertEquals($sessionSet, $session->Get());

	 // test getting default value for non-existent key
	 $this->assertEquals('default', $session->Get('nonExistent', 'default'));

  foreach ($session as $key => $value) {
	  $this->assertEquals($value, $session->Get($key));
  }

	 // test set and delete
	 $session->Set('new_test_key', 'new_test_value');
	 $this->assertEquals('new_test_value', $session->Get('new_test_key'));

	 $session->Delete('new_test_key');
	 $this->assertEquals(null, $session->Get('new_test_key'));
 }
	
 public function sessionProvider() {
	 return [
		 'blank' => [
			 'sessionSet' => []
		 ]
	 ];
 }
}

// @codingStandardsIgnoreStart
class MockableSession extends Session {
    // @codingStandardsIgnoreEnd

	private $sessionSet = [];
	
 protected function superGlobalSession(): array {
	 return $this->sessionSet;
 }
	
 public function SetSessionParams($sessionSet) {
	 $this->sessionSet = $sessionSet;
 }

 protected function startSession(): void {
	 // No return statement needed
 }

 protected function setSession($key, $value): void {
	 // No return statement needed
 }
	
 protected function unsetSession($key): void {
	 // No return statement needed
 }
}
