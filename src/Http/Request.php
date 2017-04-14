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
 * @property string RemoteIP
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

	protected $properties = [
		'Method' => 'GET',
		'PathInfo' => '/',
		'Headers' => [],
		'Environment' => null,
		'Body' => ''
	];

	/**
	 * @param Environment $environment
	 */
	public function __construct(Environment $environment) {
		$this->getParamSet    = new ParameterSet($environment['get']);
		$this->postParamSet   = new ParameterSet($environment['post']);
		$this->putParamSet    = new ParameterSet($environment['put']);
		$this->patchParamSet  = new ParameterSet($environment['patch']);
		$this->deleteParamSet = new ParameterSet($environment['delete']);

		parent::__construct([
			'Method' => $environment['method'],
			'PathInfo' => $environment['pathInfo'],
			'Headers' => $environment['headers'],
			'Environment' => $environment,
			'Body' => $environment['rebar.input']
		]);
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

	public function GetRemoteIP() {
		if (array_key_exists('X-Forwarded-For', $this->Headers)) {
			$ips = $this->Headers['X-Forwarded-For'];
		} elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $this->Headers)) {
			$ips = $this->Headers['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($this->Environment['REMOTE_ADDR'])) {
			$ips = $this->Environment['REMOTE_ADDR'];
		} else {
			$ips = '';
		}
		$ips = explode(',', $ips);

		// return the first non-private IP address (forwarded IP string will list forwarded IPs in order,
		// so the earliest non-private IP (in case the user is behind a proxy/firewall) is the user's IP
		foreach ($ips as $ip) {
			$ip = trim($ip);
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
				return $ip;
			}
		}

		// if somehow we got to this point, there is somehow only a private IP address
		if (isset($this->Environment['REMOTE_ADDR'])) {
			return $this->Environment['REMOTE_ADDR'];
		} else {
			return 'invalid';
		}
	}
}
