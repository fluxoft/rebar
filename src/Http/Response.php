<?php
namespace Fluxoft\Rebar\Http;

/**
 * Class Response
 * @package Fluxoft\Rebar\Http
 * @property int Status
 * @property string StatusMessage
 * @property string Body
 * @property array Headers
 */
class Response {
	protected $status;
	protected $statusMessage = null;
	protected $body;
	protected $headers;

	protected $messages = [
		//Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		//Successful 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		//Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		//Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		429 => 'Too Many Requests',
		//Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	];

	public function __construct(
		$status = 200,
		$body = '',
		array $headers = [
			'Content-type' => 'text/html'
		]
	) {
		$this->Status  = $status;
		$this->Body    = $body;
		$this->headers = $headers;
	}

	public function AddHeader($type, $content) {
		$this->headers[$type] = $content;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function Send() {
		header($this->getHttpHeader($this->Status));
		if (!empty($this->headers)) {
			foreach ($this->headers as $type => $content) {
				header("$type: $content");
			}
		}
		echo $this->body;
		exit;
	}

	protected function getHttpHeader($status) {
		$statusMessage = isset($this->StatusMessage) ?? $this->messages[$status];
		return 'HTTP/1.1 '.$status.' '.$statusMessage;
	}

	public function Halt($status, $body, $message = null) {
		$this->Status = $status;
		$this->Body   = $body;
		if (isset($message)) {
			$this->StatusMessage = $message;
		}
		$this->Send();
	}

	public function Redirect($location, $permanent = false) {
		$this->Status = ($permanent) ? 301 : 302;
		$this->AddHeader('Location', $location);
		$this->Body = '';
		$this->Send();
	}

	protected function getStatus() {
		return $this->status;
	}
	protected function setStatus($status) {
		if (isset($this->messages[$status])) {
			$this->status        = $status;
			$this->StatusMessage = $this->messages[$status];
		} else {
			throw new Exceptions\InvalidStatusException(sprintf('Status %s is not supported.', $status));
		}
	}
	protected function getStatusMessage() {
		return $this->statusMessage;
	}
	protected function setStatusMessage($message) {
		$this->statusMessage = $message;
	}
	protected function getBody() {
		return $this->body;
	}
	protected function setBody($value) {
		$this->body = $value;
	}

	public function __get($key) {
		$fn = "get$key";
		if (is_callable([$this, $fn])) {
			return $this->$fn();
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}
	public function __set($key, $value) {
		$fn = "set$key";
		if (is_callable([$this, $fn])) {
			$this->$fn($value);
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}
	public function __isset($key) {
		return $this->$key !== null;
	}
}
