<?php
namespace Fluxoft\Rebar\Presenters;

use \Fluxoft\Rebar\Http\Response;

/**
 * Class Smarty
 * @package Fluxoft\Rebar\Presenters
 * @property string Layout
 * @property string Template
 */
class Smarty implements PresenterInterface {
	protected $smarty = null;
	protected $templatePath;
	protected $template;
	protected $layout;

	public function __construct(\Smarty $smarty, $templatePath, $layout = 'default.tpl', $template = '') {
		$this->smarty = $smarty;
		$this->templatePath = $templatePath;
		$this->layout = $layout;
		$this->template = $template;
	}

	public function Render(Response $response, array $data) {
		if (strlen($this->template)) {
			$this->smarty->assign('templateFile', $this->templatePath.$this->template);
		}
		$this->smarty->assign($data);
		$output = $this->smarty->fetch($this->templatePath.$this->layout);

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
		}
	}
} 