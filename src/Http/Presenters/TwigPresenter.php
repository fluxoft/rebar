<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Twig
 * @package Fluxoft\Rebar\Presenters
 * @property string $Layout
 * @property string $Template
 */
class TwigPresenter implements PresenterInterface {
	/**
	 * TwigPresenter constructor.
	 * @param Environment $twig The Twig object to use for rendering.
	 * @param string $template The default template file to use.
	 */
	public function __construct(
		protected Environment $twig,
		protected string $template = '/default.html.twig'
	) {}

	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be rendered in the template.
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array {
		try {
			$output = $this->twig->render($this->template, $data);

			return [
				'body' => $output,
				'status' => 200,
				'headers' => ['Content-Type' => 'text/html']
			];
		} catch (LoaderError $e) {
			return [
				'body' => 'Template not found: ' . $this->template,
				'status' => 404,
				'headers' => ['Content-Type' => 'text/plain']
			];
		} catch (SyntaxError $e) {
			return [
				'body' => 'Template syntax error.',
				'status' => 500,
				'headers' => ['Content-Type' => 'text/plain']
			];
		} catch (RuntimeError $e) {
			return [
				'body' => 'Template runtime error.',
				'status' => 500,
				'headers' => ['Content-Type' => 'text/plain']
			];
		}
	}

	public function __set($var, $val) {
		switch ($var) {
			case 'Template': // @codeCoverageIgnore
				$this->template = $val;
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
			default:
				throw new PropertyNotFoundException(sprintf(
					'The property %s does not exist.',
					$var
				));
		}
	}
}
