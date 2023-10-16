<?php

namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

/**
 * Class Pug
 * @package Fluxoft\Rebar\Presenters
 * @property string Template
 */
class Pug implements PresenterInterface {
	/** @var string */
	protected string $template;

	protected \Pug\Pug $pug;
	protected string $templatePath;

	public function __construct(\Pug\Pug $pug, $templatePath = '') {
		$this->pug          = $pug;
		$this->templatePath = $templatePath;
	}

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
			default => throw new \InvalidArgumentException(sprintf(
				'The property %s does not exist.',
				$var
			)),
		};
	}
	public function __get($var) {
		return match ($var) {
			'Template' => $this->template,
			default => throw new \InvalidArgumentException(sprintf(
				'The property %s does not exist.',
				$var
			)),
		};
	}
}
