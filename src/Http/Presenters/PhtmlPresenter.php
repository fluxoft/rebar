<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;
use Fluxoft\Rebar\Http\Response;

/**
 * Class Phtml
 *
 * This presenter can be used to present using PHTML-style templates.
 * It's a bad idea and I don't think anyone should actually use this
 * (use Twig or Smarty or some actual template engine instead), but
 * it can be helpful when migrating a site that might already have a
 * lot of these types of templates, so they don't all have to be
 * transitioned at one time.
 *
 * @package Fluxoft\Rebar\Presenters
 * @property string $Layout
 * @property string $Template
 */
class PhtmlPresenter implements PresenterInterface {
	public function __construct(
		private readonly string $templatePath,
		private string $template = '/default.phtml',
		private string $layout = ''
	) {}

	/**
	 * Render the given data using the specified template or layout.
	 *
	 * @param Response $response The HTTP response to be updated.
	 * @param array $data The data to be rendered in the template.
	 */
	public function Render(Response $response, array $data): void {
		// make the data set available to the template as $rebarTemplateData
		// to hopefully avoid naming collisions
		$rebarTemplateData = $data;

		if (!empty($this->layout)) {
			// this can be used in a layout template to include the template
			// in the appropriate place on the page
			$rebarPageTemplate = $this->template;
			$include           = $this->templatePath.$this->layout;

			// Pass both variables for the layout template to use
			$output = $this->includeTemplate($include, compact('rebarTemplateData', 'rebarPageTemplate'));
		} else {
			$include = $this->templatePath.$this->template;

			// Pass only $rebarTemplateData for the template to use
			$output = $this->includeTemplate($include, compact('rebarTemplateData'));
		}
		if ($this->fileExists($include)) {
			$response->AddHeader('Content-Type', 'text/html');
			$response->Body   = $output;
			$response->Status = 200;
			$response->Send();
		} else {
			$response->AddHeader('Content-Type', 'text/plain');
			$response->Status = 404;
			$response->Body   = 'Template not found.';
			$response->Send();
		}

		unset($rebarTemplateData);
		unset($rebarPageTemplate);
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected function fileExists($include) {
		return file_exists($include);
	}
	/**
	 * @codeCoverageIgnore
	 */
	protected function includeTemplate(string $include, array $variables): ?string {
		if (!$this->fileExists($include)) {
			return null; // Return null if the file doesn't exist
		}

		// Extract variables into the scope of the template
		extract($variables);

		ob_start();
		include $include;
		return ob_get_clean();
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
