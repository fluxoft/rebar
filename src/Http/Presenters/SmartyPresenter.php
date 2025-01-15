<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;

/**
 * Class Smarty
 * @package Fluxoft\Rebar\Presenters
 * @property string $Layout
 * @property string $Template
 */
class SmartyPresenter implements PresenterInterface {
	/**
	 * SmartyPresenter constructor.
	 * @param \Smarty $smarty The Smarty object to use for rendering.
	 * @param string $templatePath The path to the templates.
	 * @param string $template The default template file to use.
	 * @param string $layout The layout file to use.
	 */
	public function __construct(
		private \Smarty $smarty,
		private string $templatePath,
		private string $template = '/default.html',
		private string $layout = ''
	) {}

	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be rendered in the template.
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array {
		try {
			$this->smarty->assign($data);

			$templatePath = rtrim($this->templatePath, '/') . '/';

			if ($this->layout !== '') {
				$this->smarty->assign('templateFile', $templatePath . ltrim($this->template, '/'));
				$template = $templatePath . ltrim($this->layout, '/');
			} else {
				$template = $templatePath . ltrim($this->template, '/');
			}
			$output = $this->smarty->fetch($template);

			return [
				'body' => $output,
				'status' => 200,
				'headers' => ['Content-Type' => 'text/html']
			];
		} catch (\Throwable $e) {
			return [
				'body' => 'Problem was encountered while rendering template: ' . $e->getMessage(),
				'status' => 404,
				'headers' => ['Content-Type' => 'text/plain']
			];
		}
	}

	public function __set($var, $val) {
		switch ($var) {
			case 'Template': // @codeCoverageIgnore
				$this->template = $val;
				break;
			case 'Layout': // @codeCoverageIgnore
				$this->layout = $val;
				break;
			default:
				throw new PropertyNotFoundException(sprintf(
					'The property %s does not exist.',
					$var
				));
		}
	}
	public function __get($var) {
		switch ($var) {
			case 'Template': // @codeCoverageIgnore
				return $this->template;
			case 'Layout': // @codeCoverageIgnore
				return $this->layout;
			default:
				throw new PropertyNotFoundException(sprintf(
					'The property %s does not exist.',
					$var
				));
		}
	}
}
