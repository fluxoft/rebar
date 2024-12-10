<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use Twig\Environment;

/**
 * Class Twig
 * @package Fluxoft\Rebar\Presenters
 * @property string Layout
 * @property string Template
 */
class Twig implements PresenterInterface {
	public function __construct(
		protected Environment $twig,
		protected string $template = '/default.html.twig',
		protected string $layout = ''
	) {}

	public function Render(Response $response, array $data): void {
		if (!empty($this->layout)) {
			$data['pageTemplate'] = $this->template;
			$template             = $this->layout;
		} else {
			$template = $this->template;
		}
		$output = $this->twig->render($template, $data);

		$response->Body = $output;
		$response->Send();
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
