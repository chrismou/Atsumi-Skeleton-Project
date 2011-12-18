<?php


class sql_PostgreSqlRow extends sql_AbstractRow {

	protected function parse_binary ($input) {
		return pg_unescape_bytea ($input);
	}
	
	protected function parse_boolean ($input) {
		if ($input === 't') return true;
		if ($input === 'f') return false;
		return null;
	}

	protected function parse_date ($input) {
		if (preg_match ('/^ ([0-9]{4}) - ([0-9]{2}) - ([0-9]{2}) $/x', $input, $matches))
			return new ess_Date ($matches [1], $matches [2], $matches [3]);
		return null;
	}

	protected function parse_integer ($input) {
		if (preg_match ('/^-?[0-9]+$/', $input)) return (int) $input;
		return null;
	}
	
	protected function parse_string ($input) {
		return $input;
	}

	protected function parse_timestamp ($input) {
		if (preg_match ('/^
					( [0-9]{4} - [0-9]{2} - [0-9]{2} \\s [0-9]{2} : [0-9]{2} : [0-9]{2} )
					(?: \\. ([0-9]+) )?
					( [-+] [0-9]{2} )
				$/x', $input, $matches))
			return strtotime ("{$matches[1]}{$matches[3]}");
		return null;
	}

	protected function parse_float ($input) {
		if (preg_match ('/^
					-?
					( 0 | [0-9]+ )
					( \\. [0-9]+ )?
					( e [-+] [0-9]+ )?
				$/x', $input, $matches))
			return (float) $input;
		return null;
	}
}
?>
