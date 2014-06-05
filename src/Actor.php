<?php
/**
 * Fluxoft\Rebar\Actor
 * 
 * Base class for all actor classes.  Provides essential shared
 * functionality to all actors created for an application.
 * 
 * @author Joe Hart
 *
 */
namespace Fluxoft\Rebar;

abstract class Actor {
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
	protected $data = array();
	
	/**
	 * Should be overridden in children to implement authentication
	 * depending on which action is requested.
	 * 
	 * If not overridden, always return true.
	 * 
	 * @param $action
	 * @return bool
	 */
	public function Authenticate($action) {
		return !empty($action);
	}
	
	public function Display() {
		if (!isset($this->presenter)) {
			$this->presenter = new Presenters\Debug();
		}
		if ($this->presenter instanceof Presenters\PresenterInterface) {
			$this->presenter->Render($this->GetData());
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
	protected function Set($var, $val) {
		$this->data[$var] = $val;
	}
	
	/**
	 * Return the $data array.  Used by presenter classes to 
	 * render the $data in presenter-specific ways.
	 * 
	 * @return array $data
	 */
	protected function GetData() {
		return $this->data;
	}
}
	