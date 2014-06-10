<?php
namespace Fluxoft\Rebar\Presenters;

use \Fluxoft\Rebar\Http\Response;

class Smarty implements PresenterInterface {
	protected $smarty = null;
	protected $templatePath;
	protected $template;
	protected $layout;

	public function __construct(\Smarty $smarty, $templatePath, $template, $layout = 'default.tpl') {
		$this->smarty = $smarty;
		$this->templatePath = $templatePath;
		$this->template = $template;
		$this->layout = $layout;
	}

	public function Render(Response $response, array $data) {
		$this->smarty->assign('templateFile', $this->templatePath.$this->template);
		$this->smarty->assign($data);
		$output = $this->smarty->fetch($this->templatePath.$this->layout);

		$response->Body = $output;
		$response->Send();
	}
} 