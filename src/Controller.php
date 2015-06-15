<?php
/**
 * Fluxoft\Rebar\Controller
 *
 * Base class for all controller classes.  Provides essential shared
 * functionality to all controllers created for an application.
 *
 * @author Joe Hart
 *
 */
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Auth\Exceptions\AccessDeniedException;
use Fluxoft\Rebar\Auth\AuthInterface;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;

abstract class Controller {
	/**
	 * The presenter property determines which presenter class
	 * will be used to render the display.
	 *
	 * @var \Fluxoft\Rebar\Presenters\PresenterInterface $presenter
	 */
	protected $presenter = null;
	/**
	 * The data array holds any values that need to be available to
	 * be rendered for output.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * The array of authorized methods for the controller.
	 * @var array
	 */
	protected $authorize = [];

	protected $request;
	protected $response;
	protected $auth;

	public function __construct(Request $request, Response $response, AuthInterface $auth = null) {
		$this->request  = $request;
		$this->response = $response;
		$this->auth     = $auth;
	}

	public function Authorize($method) {
		$authorized = true;
		if (isset($this->auth)) {
			if (isset($this->authorize[$method])) {
				$authUser = $this->auth->GetAuthenticatedUser();
				if ($authUser === false) {
					// method is limited and user is not authenticated
					throw new AccessDeniedException(sprintf(
						'Access denied for %s',
						$method
					));
				} else {
					if (!empty($this->authorize[$method])) {
						if (!$this->auth->UserHasRole($this->authorize[$method])) {
							throw new AccessDeniedException(sprintf(
								'User is not a member of the correct role.'
							));
						}
					}
				}
			} else {
				// method is not limited
				$authorized = true;
			}
		}
		return $authorized;
	}

	public function DenyAccess($message) {
		$this->response->Halt(403, $message);
	}

	public function Display() {
		if (!isset($this->presenter)) {
			$this->presenter = new Presenters\Debug();
		}
		if ($this->presenter instanceof Presenters\PresenterInterface) {
			$this->presenter->Render($this->response, $this->getData());
		} else {
			throw new \Exception('Invalid presenter class.');
		}
	}

	/**
	 * Add $val to the $data array with key $var.
	 *
	 * <code>
	 * $this->Set("Key","Value");
	 * </code>
	 *
	 * @param string $var
	 * @param mixed $val
	 */
	protected function set($var, $val) {
		$this->data[$var] = $val;
	}

	/**
	 * Return the $data array.  Used by presenter classes to
	 * render the $data in presenter-specific ways.
	 *
	 * @return array $data
	 */
	protected function getData() {
		return $this->data;
	}
}
