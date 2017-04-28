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
			$this->properties = parse_ini_file($iniFile, true);
		} else {
			throw new FileNotFoundException(sprintf('The ini file was not found: %s', $iniFile));
		}
	}
}
