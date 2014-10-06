<?php

namespace Fluxoft\Rebar\Presenters;

use Fluxoft\Rebar\Http\Response;

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
		$this->template = $template;
	}

	public function Render(Response $response, array $data) {
		foreach ($data as $var => $val) {
			${$var} = $val;
		}

		if (strlen($this->layout) > 0) {
			$pageTemplate = $this->template;
			include $this->templatePath.$this->layout;
		} else {
			include $this->templatePath.$this->template;
		}
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