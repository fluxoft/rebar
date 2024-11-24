<?php
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\_Traits\GettableProperties;
use Fluxoft\Rebar\_Traits\SettableProperties;
use InvalidArgumentException;

/**
 * Class Response
 * @package Fluxoft\Rebar\Http
 * @property int Status
 * @property string StatusMessage
 * @property string Body
 * @property array Headers
 */
class Response {
	use GettableProperties;
	use SettableProperties;

	protected $messages = [
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing', // WebDAV
		103 => 'Early Hints',
	
		// Successful 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status', // WebDAV
		208 => 'Already Reported', // WebDAV
		226 => 'IM Used',
	
		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
	
		// Client Error 4xx
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
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot', // Joke status
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Too Early',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
	
		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required'
	];
	

	public function __construct(
		int $status = 200,
		string $body = '',
		array $headers = ['Content-type' => 'text/html']
	) {
		$this->properties['Status']        = $status;
		$this->properties['StatusMessage'] = null;
		$this->properties['Body']          = $body;
		$this->properties['Headers']       = $headers;
	}

	public function AddHeader(string $type, string $content): void {
		$this->properties['Headers'][$type] = $content;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function Send(): void {
		header($this->getHttpHeader($this->properties['Status']));
		foreach ($this->properties['Headers'] as $type => $content) {
			header("$type: $content");
		}
		echo $this->properties['Body'];
		exit;
	}

	public function Halt(int $status, string $body, ?string $message = null): void {
		$this->properties['Status'] = $status;
		$this->properties['Body']   = $body;
		if (isset($message)) {
			$this->StatusMessage = $message;
		}
		$this->Send();
	}

	public function Redirect(string $location, bool $permanent = false): void {
		$this->properties['Status'] = ($permanent) ? 301 : 302;
		$this->AddHeader('Location', $location);
		$this->properties['Body'] = '';
		$this->Send();
	}

	protected function getHttpHeader(int $status): string {
		$statusMessage = $this->properties['StatusMessage'] // if a custom message is set
			?? $this->messages[$status]                     // if a message exists for the status
			?? 'Unknown Status';                            // if all else fails
		return "HTTP/1.1 $status $statusMessage";
	}

	protected function setStatus(int $status): void {
		if (!isset($this->messages[$status])) {
			// trigger a warning if an invalid status code is set (but still allow it)
			trigger_error(
				'Invalid status code '.$status.' set on Response object in file '.
					debug_backtrace()[0]['file'].' on line '.debug_backtrace()[0]['line'],
				E_USER_WARNING
			);
		}
		$this->properties['Status'] = $status;
	}

	// do now allow setting Headers directly
	protected function setHeaders(): void {
		throw new InvalidArgumentException('Headers is read-only');
	}
}
