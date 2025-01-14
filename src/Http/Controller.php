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
namespace Fluxoft\Rebar\Http;

use Fluxoft\Rebar\Http\Presenters\DebugPresenter;
use Fluxoft\Rebar\Http\Presenters\Exceptions\InvalidPresenterException;
use Fluxoft\Rebar\Http\Presenters\PresenterInterface;

abstract class Controller {
	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(
		protected Request $request,
		protected Response $response
	) {}

	// Everything below here is deprecated in 1.1 and will be removed in 1.3
	// @codeCoverageIgnoreStart

	/**
	 * The presenter property determines which presenter class
	 * will be used to render the display.
	 */
	protected ?PresenterInterface $presenter = null;

	/**
	 * The name of a class implementing PresenterInterface that
	 * should be used for setting the presenter if no other presenter has been set.
	 * Either a fully-qualified class name should be given or a Presenter that can be
	 * found in the \Fluxoft\Rebar\Http\Presenters namespace should be used.
	 */
	protected ?string $presenterClass = null;

	/**
	 * Uses the set PresenterInterface implementing class to Render to the Response using the internal data of the
	 * controller. If no presenter is set on the class, attempt to create one from the class name in
	 * $this->presenterClass. If that is not a class, create an instance of \Fluxoft\Rebar\Presenters\Debug and use
	 * that to Render.
	 * @throws InvalidPresenterException If no valid Presenter was set or able to be created.
	 * @deprecated Use Response::Send() instead.
	 */
	public function Display(): void {
		trigger_error('Controller::Display() is deprecated. Use Response::Send() instead.', E_USER_DEPRECATED);
		$this->response->Presenter = ($this->presenter ??= $this->initializePresenter());
		$this->response->Send();
	}
	protected function initializePresenter(): PresenterInterface {
		if (isset($this->presenterClass) && class_exists($this->presenterClass)) {
			if (!is_subclass_of($this->presenterClass, PresenterInterface::class)) {
				throw new InvalidPresenterException('Presenter must implement PresenterInterface.');
			}
			return new $this->presenterClass();
		}
		return new DebugPresenter();
	}

	/**
	 * Add $val to the $data array with key $var.
	 *
	 * <code>
	 * $this->set("Key","Value");
	 * </code>
	 * @deprecated Use Response::AddData() instead.
	 */
	protected function set(string $var, mixed $val): void {
		trigger_error('Controller::set() is deprecated. Use Response::AddData() instead.', E_USER_DEPRECATED);
		$this->response->AddData($var, $val);
	}

	/**
	 * Return the $data array.  Used by presenter classes to
	 * render the $data in presenter-specific ways.
	 * @deprecated Use Response::GetData() instead.
	 */
	protected function getData(): array {
		trigger_error('Controller::getData() is deprecated. Use Response::GetData() instead.', E_USER_DEPRECATED);
		return $this->response->GetData();
	}

	// These get/set methods are added only to allow for backwards compatibility with the deprecated presenter approach.
	// Presenter is not a property of Response, so if it is set on the controller, it needs to be set on the response
	// as well. This is only for backwards compatibility and should not be used in new code.
	// These should be removed once the deprecated presenter approach is removed.
	public function __get(string $name): mixed {
		if ($name === 'presenter') {
			trigger_error('Controller::$presenter is deprecated. Use Response::Presenter instead.', E_USER_DEPRECATED);
			return $this->response->Presenter;
		}
		return $this->$name;
	}
	public function __set(string $name, mixed $value): void {
		if ($name === 'presenter') {
			trigger_error('Controller::$presenter is deprecated. Use Response::Presenter instead.', E_USER_DEPRECATED);
			$this->response->Presenter = $value;
		} else {
			$this->$name = $value;
		}
	}

	// @codeCoverageIgnoreEnd
}
