<?php
namespace Fluxoft\Rebar\Http;

/**
 * Class Request
 * @package Fluxoft\Rebar\Http
 * @property mixed Method
 * @property mixed PathInfo
 * @property mixed Get
 * @property mixed Post
 * @property mixed Patch
 * @property mixed Put
 * @property mixed Delete
 */
class Request implements \ArrayAccess {
	/**
	 * @var array
	 */
	protected $properties;

	/**
	 * @param Environment $environment
	 */
	public function __construct(Environment $environment) {
		$props             = array();
		$props['Method']   = strtoupper($environment['REQUEST_METHOD']);
		$props['PathInfo'] = $environment['PATH_INFO'];
		$props['Get']      = $_GET;
		$props['Post']     = $_POST;
		$this->properties  = $props;
	}

	public function __get($var) {
		switch ($var) {
			case 'Method':
			case 'PathInfo':
			case 'Get':
				$rtn = $this->properties[$var];
				break;
			case 'Post':
			case 'Put':
			case 'Delete':
			case 'Patch':
				$rtn = $this->properties['Post'];
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $var));
		}
		return $rtn;
	}

	// ArrayAccess
	public function offsetExists($offset) {
		switch ($offset) {
			case 'Method':
			case 'PathInfo':
			case 'Get':
			case 'Post':
			case 'Put':
			case 'Delete':
			case 'Patch':
				$rtn = true;
				break;
			default:
				$rtn = false;
				break;
		}
		return $rtn;
	}
	public function offsetGet($offset) {
		switch ($offset) {
			case 'Method':
			case 'PathInfo':
			case 'Get':
				$rtn = $this->properties[$offset];
				break;
			case 'Post':
			case 'Put':
			case 'Delete':
			case 'Patch':
				$rtn = $this->properties['Post'];
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $offset));
		}
		return $rtn;
	}
	public function offsetSet($offset, $value) {
		throw new \InvalidArgumentException('Read-only object.');
	}
	public function offsetUnset($offset) {
		throw new \InvalidArgumentException('Read-only object.');
	}
}