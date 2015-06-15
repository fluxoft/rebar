<?php

namespace Fluxoft\Rebar;

/**
 * Interface FactoryInterface
 * @package Fluxoft\Rebar
 */
interface FactoryInterface {
	/**
	 * @param $className
	 * @return mixed
	 */
	public function Build($className);
}
