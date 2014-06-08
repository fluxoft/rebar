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
	protected $body;
	protected $headers;

	protected $messages = array(
		//Informational 1xx
		100 => '100 Continue',
		101 => '101 Switching Protocols',
		//Successful 2xx
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',
		//Redirection 3xx
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		306 => '306 (Unused)',
		307 => '307 Temporary Redirect',
		//Client Error 4xx
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		418 => '418 I\'m a teapot',
		422 => '422 Unprocessable Entity',
		423 => '423 Locked',
		//Server Error 5xx
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported'
	);

	public function __construct(
		$status = 200,
		$body = '',
		array $headers = array(
			'Content-type' => 'text/html'
		)
	) {
		$this->status = $status;
	}

	public function AddHeader($type, $content) {
		$this->headers[$type] = $content;
	}

	public function Send() {
		header('HTTP/1.1 '.$this->messages[$this->status]);
		if (!empty($this->headers)) {
			foreach ($this->headers as $type => $content) {
				header("$type: $content");
			}
		}
		echo $this->body;
	}

	public function Redirect($location) {
		$this->status = 302;
		$this->AddHeader('Location', $location);
		$this->body = '';
		$this->Send();
	}


	private function getStatus() {
		return $this->status;
	}
	private function setStatus($value) {
		if (isset($this->messages[$value])) {
			$this->status = $value;
		} else {
			throw new Exceptions\InvalidStatusException(sprintf('Status %s is not supported.', $value));
		}
	}
	private function getBody() {
		return $this->body;
	}
	private function setBody($value) {
		$this->body = $value;
	}

	public function __get($key) {
		$fn = "get$key";
		if (method_exists($this, $fn)) {
			return $this->$fn();
		} else {
			throw new \InvalidArgumentExceptionException(sprintf('Cannot get property: \'%s\' does not exist', $key));
		}
	}
	public function __set($key, $value) {
		$fn = "set$key";
		if (method_exists($this, $fn)) {
			$this->$fn($value);
		} else {
			throw new \InvalidArgumentExceptionException(sprintf('Cannot set property: \'%s\' does not exist', $key));
		}
	}
}