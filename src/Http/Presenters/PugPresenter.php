<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Pug\Pug;

/**
 * Class Pug
 * @package Fluxoft\Rebar\Presenters
 * @property string $Template
 */
class PugPresenter implements PresenterInterface {
	/**
	 * PugPresenter constructor.
	 * @param Pug $pug The Pug object to use for rendering.
	 * @param string $templatePath The path to the templates.
	 * @param string $template The default template file to use.
	 */
	public function __construct(
		private Pug $pug,
		private readonly string $templatePath,
		private string $template = '/default.pug'
	) {}

	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be rendered in the template.
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array {
		try {
			$output = $this->pug->renderFile($this->templatePath.$this->template, ['data' => $data]);

			return [
				'body' => $output,
				'status' => 200,
				'headers' => ['Content-Type' => 'text/html']
			];
		} catch (\Throwable $e) {
			return [
				'body' => 'Template not found.',
				'status' => 404,
				'headers' => ['Content-Type' => 'text/plain']
			];
		}
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
