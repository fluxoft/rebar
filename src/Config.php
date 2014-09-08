<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Exceptions\FileNotFoundException;

class Config implements \ArrayAccess {
	private $config = array();
	public function __construct($iniFile) {
		if (file_exists($iniFile)) {
			$this->config = parse_ini_file($iniFile,true);
		} else {
			throw new FileNotFoundException(sprintf('The ini file was not found: %s', $iniFile));
		}
	}

	// ArrayAccess
	public function offsetExists($offset) {
		return isset($this->config[$offset]);
	}
	public function offsetGet($offset) {
		if (!isset($this->config[$offset])) {
			throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $offset));
		}
		return $this->config[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->config[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->config[$offset]);
	}
}