<?php
namespace Fluxoft\Rebar\Http\Presenters;

use \Fluxoft\Rebar\Http\Response;

/**
 * Class Smarty
 * @package Fluxoft\Rebar\Presenters
 * @property string Layout
 * @property string Template
 */
class Smarty implements PresenterInterface {
	/**
	 * @var \Smarty
	 */
	protected $smarty;
	protected $templatePath;
	protected $template;
	protected $layout;

	public function __construct(
		\Smarty $smarty,
		$templatePath,
		$template = '/default.html',
		$layout = ''
	) {
		$this->smarty       = $smarty;
		$this->templatePath = $templatePath;
		$this->template     = $template;
		$this->layout       = $layout;
	}

	public function Render(Response $response, array $data) {
		$this->smarty->assign($data);
		if (strlen($this->layout)) {
			$this->smarty->assign('templateFile', $this->templatePath.$this->template);
			$template = $this->templatePath.$this->layout;
		} else {
			$template = $this->templatePath.$this->template;
		}
		$output = $this->smarty->fetch($template);

		$response->Body = $output;
		$response->Send();
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
