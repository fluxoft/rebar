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
	 * The data array holds any values that need to be available to
	 * be rendered for output.
	 */
	protected array $data = [];

	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function __construct(
		protected Request $request,
		protected Response $response
	) {}

	/**
	 * Uses the set PresenterInterface implementing class to Render to the Response using the internal data of the
	 * controller. If no presenter is set on the class, attempt to create one from the class name in
	 * $this->presenterClass. If that is not a class, create an instance of \Fluxoft\Rebar\Presenters\Debug and use
	 * that to Render.
	 * @throws InvalidPresenterException If no valid Presenter was set or able to be created.
	 */
	public function Display(): void {
		$this->presenter ??= $this->initializePresenter();
		$this->presenter->Render($this->response, $this->getData());
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
	 */
	protected function set(string $var, mixed $val): void {
		$this->data[$var] = $val;
	}

	/**
	 * Return the $data array.  Used by presenter classes to
	 * render the $data in presenter-specific ways.
	 */
	protected function getData(): array {
		return $this->data;
	}
}
