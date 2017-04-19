<?php

namespace Fluxoft\Rebar\Error;

/**
 * The most basic of notifications: print the exception message to the screen and exit.
 * Class BasicNotifier
 * @package Fluxoft\Rebar\Error
 */
class BasicNotifier implements NotifierInterface {
	/**
	 * Should be overridden in a Notifier class to accept an unhandled exception and do
	 * something with it. These classes should be very careful to handle all possible
	 * exceptions of their own in a graceful way so as not to cause a
	 * @param \Throwable $t
	 * @return void
	 */
	public function Notify(\Throwable $t) {
		header('HTTP/1.1 500 Unhandled exception');
		header('content-type: text/plain');
		echo "******************************\n";
		echo "***  Unhandled exception:  ***\n";
		echo "******************************\n";
		echo "\n";
		echo (string) $t;
		exit;
	}
}
