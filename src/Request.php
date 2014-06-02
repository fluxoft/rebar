<?php
namespace Fluxoft\Rebar;

class Request implements \ArrayAccess {
	protected $values = array();
	
	public function __construct() {
		$this->values['server'] = $_SERVER;
		$this->values['request'] = $_REQUEST;
		$this->values['get'] = $_GET;
		$this->values['post'] = $_POST;
	}
	
	// ArrayAccess
	public function offsetExists($offset) {
		return isset($this->values[$offset]);
	}
	public function offsetGet($offset) {
		if (!isset($this->values[$offset])) {
			throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $offset));
		}
		return $this->values[$offset];
	}
	public function offsetSet($offset, $value) {
		throw new \InvalidArgumentException('Read-only object.');
	}
	public function offsetUnset($offset) {
		throw new \InvalidArgumentException('Read-only object.');
	}
}
	