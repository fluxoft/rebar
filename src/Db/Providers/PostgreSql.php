<?php
namespace Fluxoft\Rebar\Db\Providers;

class PostgreSql extends Provider {
	public function Insert ($query, array $params = null, $sequence = '') {
		$this->sequence = $sequence;
		return parent::Insert($query, $params);
	}
}