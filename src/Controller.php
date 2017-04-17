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
use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;
use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Presenters\Exceptions\InvalidPresenterException;

abstract class Controller {
	/**
	 * If CORS requests need to be handled automatically by the Controller,
	 * set this to true to add the appropriate headers to responses based on
	 * the Origin header provided.
	 * @var bool
	 */
	protected $crossOriginEnabled = false;
	/**
	 * An array of domains from which cross-origin requests are allowed.
	 * @var array
	 */
	protected $crossOriginDomainsAllowed = [];
	/**
	 * Methods that are allowed to this controller. If a controller method needs
	 * a different set of allowed methods, this array should be reset inside the
	 * method.
	 * @var array
	 */
	protected $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
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

	/** @var Request */
	protected $request;
	/** @var Response */
	protected $response;
	/** @var AuthInterface */
	protected $auth;

	/**
	 * Controller constructor.
	 * @param Request $request
	 * @param Response $response
	 * @param AuthInterface|null $auth
	 */
	public function __construct(
		Request $request,
		Response $response,
		AuthInterface $auth = null
	) {
		$this->request  = $request;
		$this->response = $response;
		$this->auth     = $auth;
	}

	/**
	 * @param $method
	 * @return bool
	 * @throws AccessDeniedException
	 * @throws CrossOriginException
	 * @throws MethodNotAllowedException
	 */
	public function Authorize($method) {
		$allowedMethods = array_map('strtoupper', $this->allowedMethods);
		$requestHeaders = $this->request->Headers;
		$requestMethod  = $this->request->Method;

		// always allow OPTIONS requests
		if (!in_array('OPTIONS', $allowedMethods)) {
			array_push($allowedMethods, 'OPTIONS');
		}
		// set CORS headers if configured
		if ($this->crossOriginEnabled) {
			if (isset($requestHeaders['Origin'])) {
				$allowedHeaders = (isset($requestHeaders['Access-Control-Request-Headers']) ?
					$requestHeaders['Access-Control-Request-Headers'] : '');
				$origin         = $requestHeaders['Origin'];
				if (in_array($origin, $this->crossOriginDomainsAllowed)) {
					$this->response->AddHeader('Access-Control-Allow-Origin', $origin);
					$this->response->AddHeader('Access-Control-Allow-Credentials', 'true');
					$this->response->AddHeader('Access-Control-Allow-Methods', implode(',', $allowedMethods));
					$this->response->AddHeader('Access-Control-Allow-Headers', $allowedHeaders);
				} else {
					throw new CrossOriginException(sprintf('The origin "%s" is not permitted.', $origin));
				}
			}
		}
		if (!in_array($requestMethod, $allowedMethods)) {
			throw new MethodNotAllowedException(sprintf(
				'The %s method is not permitted here (118).',
				$requestMethod
			));
		}
		/*
		 * Issue #30: Authorize any OPTIONS request.
		 */
		if (strtoupper($requestMethod) === 'OPTIONS') {
			return true;
		}
		if (isset($this->auth) && $this->methodRequiresAuthentication($method)) {
			/** @var \Fluxoft\Rebar\Auth\Reply $authReply */
			$authReply = $this->auth->GetAuthenticatedUser($this->request);
			if (!$authReply->Auth) {
				// method is limited and user is not authenticated
				throw new AccessDeniedException(
					sprintf('Access denied for %s', $method)
				);
			}
		}
		return true;
	}

	/**
	 * If a method has been marked as skipped or all methods are skipped with an element
	 * of "*", do not require authentication.
	 * If method not skipped, and requireAuthentication is empty, or the method is set
	 * as required, or all methods require authentication ("*" element in array), require
	 * authentication.
	 * @param $method
	 * @return bool
	 */
	protected function methodRequiresAuthentication($method) {
		$requiresAuth = false;
		if (in_array($method, $this->skipAuthentication) ||
			in_array('*', $this->skipAuthentication)
		) {
			$requiresAuth = false;
		} else {
			if (empty($this->requireAuthentication) ||
				in_array($method, $this->requireAuthentication) ||
				in_array('*', $this->requireAuthentication)
			) {
				$requiresAuth = true;
			}
		}
		return $requiresAuth;
	}

	/**
	 * Uses the set PresenterInterface implementing class to Render to the Response using the internal data of the
	 * controller. If no presenter is set on the class, atttempt to create one from the class name in
	 * $this->presenterClass. If that is not a class, create an instance of \Fluxoft\Rebar\Presenters\Debug and use
	 * that to Render.
	 * @throws InvalidPresenterException If no valid Presenter was set or able to be created.
	 */
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
			throw new InvalidPresenterException('Presenter must implement PresenterInterface.');
		}
	}

	/**
	 * Add $val to the $data array with key $var.
	 *
	 * <code>
	 * $this->set("Key","Value");
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
