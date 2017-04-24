<?php

namespace Fluxoft\Rebar\Presenters;

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
 * @property string Layout
 * @property string Template
 */
class Phtml implements PresenterInterface {
	protected $templatePath;
	protected $template;
	protected $layout;

	public function __construct(
		$templatePath,
		$template = '/default.phtml',
		$layout = ''
	) {
		$this->templatePath = $templatePath;
		$this->template     = $template;
		$this->layout       = $layout;
	}

	public function Render(Response $response, array $data) {
		// make the data set available to the template as $rebarTemplateData
		// to hopefully avoid naming collisions
		$rebarTemplateData = $data;

		if (strlen($this->layout) > 0) {
			// this can be used in a layout template to include the template
			// in the appropriate place on the page
			$rebarPageTemplate = $this->template;
			$include           = $this->templatePath.$this->layout;
		} else {
			$include = $this->templatePath.$this->template;
		}
		if ($this->fileExists($include)) {
			$this->includeTemplate($include);
			$response = null;
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
	protected function includeTemplate($include) {
		include ($include);
		exit;
	}

	public function __set($var, $val) {
		switch ($var) {
			case 'Template':
				$this->template = $val;
				break;
			case 'Layout':
				$this->layout = $val;
				break;
			default:
				throw new \InvalidArgumentException(sprintf(
					'The property %s does not exist.',
					$var
				));
				break;
		}
	}
	public function __get($var) {
		switch ($var) {
			case 'Template':
				return $this->template;
				break;
			case 'Layout':
				return $this->layout;
				break;
			default:
				throw new \InvalidArgumentException(sprintf(
					'The property %s does not exist.',
					$var
				));
				break;
		}
	}
}
