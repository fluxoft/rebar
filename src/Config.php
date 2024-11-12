<?php
namespace Fluxoft\Rebar;

use Fluxoft\Rebar\Exceptions\FileNotFoundException;

/**
 * Class Config
 * @package Fluxoft\Rebar
 * @codeCoverageIgnore
 */
class Config extends Model {
	public function __construct($iniFile) {
		if (file_exists($iniFile)) {
			$iniFile = parse_ini_file($iniFile, true) ?? [];
			parent::__construct($iniFile);
		} else {
			throw new FileNotFoundException(sprintf('The ini file was not found: %s', $iniFile));
		}
	}
}
