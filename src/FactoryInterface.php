<?php

namespace Fluxoft\Rebar;

/**
 * Interface FactoryInterface
 * @package Fluxoft\Rebar
 */
interface FactoryInterface {
	/**
	 * @param string $className
	 * @param array $extra
	 * @return mixed
	 */
	public function Build(string $className, array $extra = []);
}
