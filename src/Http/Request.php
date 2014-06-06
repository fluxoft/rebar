<?php
namespace Fluxoft\Rebar\Http;

class Request {
	/**
	 * @var Environment
	 */
	protected $env;

	public function __construct(Environment $environment) {
		$this->env = $environment;
	}

	public function GetMethod() {
		return $this->env['REQUEST_METHOD'];
	}

	public function Params($key = null) {
		$union = array_merge($this->Get(), $this->Post());
		if ($key) {
			return (isset($union[$key])) ? $union[$key] : null;
		} else {
			return $union;
		}
	}

	public function Get($key = null) {
		if ($key) {
			return (isset($_GET[$key])) ? $_GET[$key] : null;
		} else {
			return $_GET;
		}
	}

	public function Post($key = null) {
		if ($key) {
			return (isset($_POST[$key])) ? $_POST[$key] : null;
		} else {
			return $_POST;
		}
	}
}
	