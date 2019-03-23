<?php

namespace Fluxoft\Rebar\_Traits;

trait UnsettableProperties {
	/**
	 * Override the magic __set() method to prevent setting values.
	 *
	 * @param $key
	 * @param $value
	 * @throws \InvalidArgumentException
	 */
	public function __set($key, $value) {
		//unused
		$key   = null;
		$value = null;
		throw new \InvalidArgumentException('Read-only object.');
	}

	/**
	 * Override the magic __unset method to prevent unsetting.
	 *
	 * @param $key
	 * @throws \InvalidArgumentException
	 */
	public function __unset($key) {
		//unused
		$key = null;
		throw new \InvalidArgumentException('Read-only object.');
	}
}
