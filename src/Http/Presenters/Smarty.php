<?php
namespace Fluxoft\Rebar\Http\Presenters;

use Fluxoft\Rebar\Exceptions\PropertyNotFoundException;

use \Fluxoft\Rebar\Http\Response;

/**
 * Class Smarty
 * @package Fluxoft\Rebar\Presenters
 * @property string Layout
 * @property string Template
 */
class Smarty implements PresenterInterface {
	public function __construct(
		private \Smarty $smarty,
		private string $templatePath,
		private string $template = '/default.html',
		private string $layout = ''
	) {}

	public function Render(Response $response, array $data): void {
		$this->smarty->assign($data);
	
		$templatePath = rtrim($this->templatePath, '/') . '/';
	
		if ($this->layout !== '') {
			$this->smarty->assign('templateFile', $templatePath . ltrim($this->template, '/'));
			$template = $templatePath . ltrim($this->layout, '/');
		} else {
			$template = $templatePath . ltrim($this->template, '/');
		}
		$output = $this->smarty->fetch($template);
	
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
