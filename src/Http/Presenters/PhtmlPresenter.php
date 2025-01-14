<?php

namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;

/**
 * Class Phtml
 *
 * This presenter can be used to present using PHTML-style templates.
 * It's a bad idea and I don't think anyone should actually use this
 * (use Twig or Pug or some actual template engine instead), but
 * it can be helpful when migrating a site that might already have a
 * lot of these types of templates, so they don't all have to be
 * transitioned at one time.
 *
 * @package Fluxoft\Rebar\Presenters
 * @property string $Layout
 * @property string $Template
 */
class PhtmlPresenter implements PresenterInterface {
	/**
	 * PhtmlPresenter constructor.
	 * @param string $templatePath The path to the templates.
	 * @param string $template The default template file to use.
	 * @param string $layout The layout file to use.
	 */
	public function __construct(
		protected readonly string $templatePath,
		protected string $template = '/default.phtml',
		protected string $layout = ''
	) {}

	/**
	 * Transform the given data into the desired output format.
	 *
	 * @param array $data The data to be rendered in the template.
	 * @return array{body: string, status: int, headers: array<string, string>}
	 */
	public function Format(array $data): array {
		// make the data set available to the template as $rebarTemplateData
		// to hopefully avoid naming collisions
		$rebarTemplateData = $data;

		if (!empty($this->layout)) {
			// this can be used in a layout template to include the template
			// in the appropriate place on the page
			$rebarPageTemplate = $this->template;
			$include           = $this->templatePath . $this->layout;

			// Pass both variables for the layout template to use
			$output = $this->includeTemplate($include, compact('rebarTemplateData', 'rebarPageTemplate'));
		} else {
			$include = $this->templatePath . $this->template;

			// Pass only $rebarTemplateData for the template to use
			$output = $this->includeTemplate($include, compact('rebarTemplateData'));
		}

		if ($this->fileExists($include)) {
			// Successful rendering
			return [
				'body' => $output,
				'status' => 200,
				'headers' => ['Content-Type' => 'text/html']
			];
		} else {
			// Template not found
			return [
				'body' => 'Template not found.',
				'status' => 404,
				'headers' => ['Content-Type' => 'text/plain']
			];
		}
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
