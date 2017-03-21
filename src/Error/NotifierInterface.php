<?php

namespace Fluxoft\Rebar\Error;

interface NotifierInterface {
	/**
	 * Should be overridden in a Notifier class to accept an unhandled exception and do
	 * something with it. These classes should be very careful to handle all possible
	 * exceptions of their own in a graceful way so as not to cause a
	 * @param \Throwable|\Exception $t
	 * @return mixed
	 */
	public function Notify($t);
}
