<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Model;

/**
 * Class Request
 * @package Fluxoft\Rebar\Http
 * @property mixed Method
 * @property mixed PathInfo
 * @property mixed Headers
 * @property mixed Environment
 * @property mixed Body
 */
class Request extends Model {
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
		$this->properties['Method']      = $environment['method'];
		$this->properties['PathInfo']    = $environment['pathInfo'];
		$this->properties['Headers']     = $environment['headers'];
		$this->properties['Environment'] = $environment;
		$this->properties['Body']        = $environment['rebar.input'];

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
}
