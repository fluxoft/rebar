<?php
namespace Fluxoft\Rebar\Http;

/**
 * Class Response
 * @package Fluxoft\Rebar\Http
 * @property int Status
 * @property string Body
 * @property array Headers
 */
class Response {
	protected $status;
	protected $statusMessage;
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
		$this->status  = $status;
		$this->body    = $body;
		$this->headers = $headers;
	}

	public function AddHeader($type, $content) {
		$this->headers[$type] = $content;
	}

	public function Send() {
		header('HTTP/1.1 '.$this->status.' '.$this->messages[$this->status]);
		if (!empty($this->headers)) {
			foreach ($this->headers as $type => $content) {
				header("$type: $content");
			}
		}
		echo $this->body;
		exit;
	}

	public function Halt($status, $message) {
		$this->Status = $status;
		$this->Body   = $message;
		$this->Send();
	}

	public function Redirect($location) {
		$this->status = 302;
		$this->AddHeader('Location', $location);
		$this->body = '';
		$this->Send();
	}

	protected function getStatus() {
		return $this->status;
	}
	protected function setStatus($status) {
		if (is_array($status)) {
			$this->status        = $status[0];
			$this->statusMessage = $status[1];
		} else {
			$code = $status;
			if (isset($this->messages[$code])) {
				$this->status        = $code;
				$this->statusMessage = $this->messages[$code];
			} else {
				throw new Exceptions\InvalidStatusException(sprintf('Status %s is not supported.', $value));
			}
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
		if (method_exists($this, $fn)) {
			return $this->$fn();
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}
	public function __set($key, $value) {
		$fn = "set$key";
		if (method_exists($this, $fn)) {
			$this->$fn($value);
		} else {
			throw new \InvalidArgumentException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}
}
