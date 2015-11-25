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
	 * @var null|string The name of a class implementing PresenterInterface that
	 * should be used for setting the presenter if no other presenter has been set.
	 * Either a fully-qualified class name should be given or a Presenter that can be
	 * found in the \Fluxoft\Rebar\Presenters namespace should be used.
	 */
	protected $presenterClass = null;
	/**
	 * The data array holds any values that need to be available to
	 * be rendered for output.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var array The array of methods for which authentication is required.
	 */
	protected $requireAuthentication = [];

	/**
	 * @var array The array of methods for which authentication can be skipped.
	 */
	protected $skipAuthentication = ['*'];

	protected $request;
	protected $response;
	protected $auth;

	public function __construct(Request $request, Response $response, AuthInterface $auth = null) {
		$this->request  = $request;
		$this->response = $response;
		$this->auth     = $auth;
	}

	/**
	 * Can be overridden in child classes to perform setup functions (like setting up defaults).
	 * @return bool
	 */
	public function Setup() {
		return true;
	}

	/**
	 * Can be overridden in child classes to perform cleanup functions
	 * @return bool
	 */
	public function Cleanup() {
		return true;
	}

	public function Authorize($method) {
		$authorized = true;
		if (isset($this->auth)) {
			if (!(in_array($method, $this->skipAuthentication) ||
				in_array('*', $this->skipAuthentication))
			) {
				$requireAuth = false;
				// If requireAuthentication is empty, prevent access by default.
				if (empty($this->requireAuthentication)) {
					$requireAuth = true;
				} else {
					if (in_array($method, $this->requireAuthentication) ||
						in_array('*', $this->requireAuthentication)
					) {
						$requireAuth = true;
					}
				}
				if ($requireAuth) {
					$authUser = $this->auth->GetAuthenticatedUser();
					if ($authUser === false) {
						// method is limited and user is not authenticated
						throw new AccessDeniedException(sprintf(
							'Access denied for %s',
							$method
						));
					}
				}
			}
		}
		return $authorized;
	}

	public function DenyAccess($message) {
		$this->response->Halt(403, "Forbidden: $message");
	}

	public function Display() {
		if (!isset($this->presenter)) {
			if (isset($this->presenterClass)) {
				if (class_exists($this->presenterClass)) {
					$this->presenter = new $this->presenterClass();
				} else {
					$class = '\\Fluxoft\\Rebar\\Presenters\\'.$this->presenterClass;
					if (class_exists($class)) {
						$this->presenter = new $class();
					}
				}
			} else {
				$this->presenter = new Presenters\Debug();
			}
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
