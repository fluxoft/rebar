<?php

namespace Fluxoft\Rebar;

class Utils {
	/**
	 * Generate a GUID
	 * @return string
	 */
	public static function GetUUID() {
		if (function_exists('com_create_guid')) {
			return com_create_guid();
		}
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45); // "-"
		$uuid   = chr(123) // "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid, 12, 4).$hyphen
			.substr($charid, 16, 4).$hyphen
			.substr($charid, 20, 12)
			.chr(125); // "}"
		return $uuid;
	}
}
