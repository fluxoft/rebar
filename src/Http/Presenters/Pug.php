<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;

/**
 * Class Pug
 * @package Fluxoft\Rebar\Presenters
 * @property string $Template
 */
class Pug implements PresenterInterface {
	protected string $template;

	public function __construct(
		protected \Pug\Pug $pug,
		protected string $templatePath = ''
	) {}

	/**
	 * @throws \Exception
	 */
	public function Render(Response $response, array $data): void {
		$output = $this->pug->renderFile($this->templatePath.$this->template, ['data' => $data]);

		$response->Body = $output;
		$response->Send();
	}

	public function __set($var, $val) {
		$this->template = match ($var) {
			'Template' => $val,
			default => throw new PropertyNotFoundException(sprintf(
				'The property %s does not exist.',
				$var
			)),
		};
	}
	public function __get($var) {
		return match ($var) {
			'Template' => $this->template,
			default => throw new PropertyNotFoundException(sprintf(
				'The property %s does not exist.',
				$var
			)),
		};
	}
}
