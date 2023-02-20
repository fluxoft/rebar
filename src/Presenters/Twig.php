<?php
namespace Fluxoft\Rebar\Presenters;

use \Fluxoft\Rebar\Http\Response;
use Twig\Environment;

/**
 * Class Twig
 * @package Fluxoft\Rebar\Presenters
 * @property string Layout
 * @property string Template
 */
class Twig implements PresenterInterface {
	/** @var Environment */
	protected $twig;
	protected $templatePath;
	protected $template;
	protected $layout;

	public function __construct(
		Environment $twig,
		$template = '/default.html.twig',
		$layout = ''
	) {
		/*$loader = new \Twig\Loader\FilesystemLoader($templatePath);
		$twig   = new \Twig\Environment($loader, array(
			'cache' => $cachePath,
			'debug' => $debug
		));*/

		$this->twig     = $twig;
		$this->template = $template;
		$this->layout   = $layout;
	}

	public function Render(Response $response, array $data) {
		if (strlen($this->layout)) {
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
