<?php
namespace Fluxoft\Rebar\Http;

/**
 * Class Request
 * @package Fluxoft\Rebar\Http
 * @property mixed Method
 * @property mixed PathInfo
 * @property mixed Headers
 * @property mixed Environment
 */
class Request {
	/** @var array */
	protected $properties;
	/** @var \Fluxoft\Rebar\Http\Environment */
	protected $environment;
	/** @var ParameterSet  */
	protected $getParamSet;
	/** @var ParameterSet  */
	protected $postParamSet;
	/** @var ParameterSet  */
	protected $putParamSet;
	/** @var ParameterSet  */
	protected $patchParamSet;
	/** @var ParameterSet  */
	protected $deleteParamSet;

	/**
	 * @param Environment $environment
	 */
	public function __construct(Environment $environment) {
		$props             = array();
		$props['Method']   = $environment['method'];
		$props['PathInfo'] = $environment['PATH_INFO'];
		$props['Headers']  = $environment['headers'];
		$this->properties  = $props;
		$this->environment = $environment;

		$this->getParamSet    = new ParameterSet($environment['get']);
		$this->postParamSet   = new ParameterSet($environment['post']);
		$this->putParamSet    = new ParameterSet($environment['put']);
		$this->patchParamSet  = new ParameterSet($environment['patch']);
		$this->deleteParamSet = new ParameterSet($environment['delete']);
	}

	public function Get($var = null, $default = null) {
		return $this->getParamSet->Get($var, $default);
	}
	public function Post($var = null, $default = null) {
		return $this->postParamSet->Get($var, $default);
	}
	public function Patch($var = null, $default = null) {
		return $this->patchParamSet->Get($var, $default);
	}
	public function Put($var = null, $default = null) {
		return $this->putParamSet->Get($var, $default);
	}
	public function Delete($var = null, $default = null) {
		return $this->deleteParamSet->Get($var, $default);
	}

	public function __get($var) {
		switch ($var) {
			case 'Method':
			case 'PathInfo':
			case 'Headers':
				$rtn = $this->properties[$var];
				break;
			case 'Environment':
				$rtn = $this->environment;
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Value "%s" is not defined.', $var));
		}
		return $rtn;
	}
}
