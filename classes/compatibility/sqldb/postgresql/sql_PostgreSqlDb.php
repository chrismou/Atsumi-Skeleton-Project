<?
class sql_PostgreSqlDb extends sql_AbstractDb {

	private $link = null;
	private $transaction = false;
	private $last_affected_rows = null;
	private $state = 'disconnected';

	public function __construct () {
		parent::__construct ("postgresql_db");
	}

	// -------------------------------------------------------- configuration and stuff

	public function connect ($connect) {
		if ($this->state != 'disconnected')
			throw new sql_Exception ('Called connect in invalid state: ' . $this->state);
		$this->link = @pg_connect ($connect);

		if (! $this->link)
			throw new sql_ConnectException ('Connect error');

		$this->state = 'connected';

	}

	public function last_error () {
		return pg_last_error ($this->link);
	}

	function set_client_encoding ($encoding) {
		if (0 != pg_set_client_encoding ($this->link, $encoding))
			throw new sql_Exception ($this->last_error ());
	}

	protected function get_id_preg () {
		return ' [a-z_][a-z0-9_]* | " (?: [^"] | "" )* " '; 
	}

	public function link () {
		return $this->link;
	}

	// -------------------------------------------------------- quoting rules
	
	public function quote_raw ($s) {
		return sprintf ('\'%s\'', pg_escape_string ($s));
	}

	public function quote_string ($s) {
		return sprintf ('\'%s\'::text', pg_escape_string ($s));
	}

	public function quote_integer ($i) {
		return sprintf ('\'%d\'::integer', $i);
	}

	public function quote_boolean ($b) {
		return sprintf ('\'%s\'::boolean', $b? 't' : 'f');
	}

	public function quote_timestamp ($d) {
		return sprintf ('\'%s\'::timestamp with time zone', pg_escape_string (
			gmdate ('Y-m-d H:i:s+00', $d)));
	}

	public function quote_binary ($x) {
		return sprintf ('\'%s\'::bytea', pg_escape_bytea ($x));
	}

	public function quote_identifier ($z) {
		return sprintf ('"%s"', str_replace ('"', '""', $z));
	}

	public function quote_date (ess_Date $d) {
		return sprintf ('\'%s\'::date', pg_escape_string (
			sprintf ('%04d-%02d-%02d', $d->year (), $d->month (), $d->day ())));
	}

	// -------------------------------------------------------- internal quoting rules
	
	public function quote_internal_b ($b) {
		return $b? 't' : 'f';
	}

	public function quote_internal_d (ess_Date $d) {
		return $d->ymd ();
	}

	public function quote_internal_f ($i) {
		return (float) $i;
	}

	public function quote_internal_i ($i) {
		return (string) $i;
	}

	public function quote_internal_r ($r) {
		return $r;
	}

	public function quote_internal_s ($s) {
		return $s;
	}

	public function quote_internal_t ($t) {
		return gmdate ('Y-m-d H:i:s+00', $t);
	}

	public function quote_internal_x ($x) {
		return pg_escape_bytea ($x);
	}

	// -------------------------------------------------------- internal functions 

	public function query_real ($query) {

		if ($this->state != 'connected')
			throw new sql_Exception ('Not connected');
		
		if ($this->recordQueryTimes) {
			$timestart = microtime();	
		}
		
		// reset affected rows
		$this->last_affected_rows = null;

		// do the query
		$this->debug ('query', 'query ' . $query);
		//pf ('<p>%h</p>\n', $query);
		$result = @pg_query ($this->link, $query);
		if (! $result) {
			$this->debug ("query_error", "error " . $this->last_error ());
			throw new sql_QueryException ($this->last_error ());
		}

		// get the results
		$rows = array ();
		while ($row = pg_fetch_array ($result, null, PGSQL_ASSOC))
			$rows[] = new sql_PostgreSqlRow ($row);
		$results = array ($rows);

		// get affected rows
		$this->last_affected_rows = pg_affected_rows ($result);

		// clean up
		pg_free_result ($result);

		if ($this->recordQueryTimes) {
			$this->addQueryTime($query, round((microtime() - $timestart),4));	
		}
	
		// return
		return $results;
	}

	function affected_rows () {
		if (is_null ($this->last_affected_rows))
			throw new sql_Exception ("No affected_rows available");
		return $this->last_affected_rows;
	}

	public function nextval ($name_etc) {
		$args = func_get_args ();
		$name = $this->quotef_array ($args);
		$row = $this->select_1 ("SELECT nextval (%s) AS id", $name);
		return $row->i ("id"); 
	}

	// -------------------------------------------------------- transactions

	public function supports_transactions () {
		return true;
	}

	public function begin () {
		if ($this->transaction)
			throw new sql_Exception ("Called begin() while in transaction.");
		$this->query ("BEGIN");
		$this->transaction = true;
		return true;
	}

	public function rollback () {
		if (! $this->transaction)
			throw new sql_Exception ("Called rollback() while not in transaction.");
		$this->transaction = false;
		return $this->query ("ROLLBACK");
	}

	public function auto_rollback () {
		if ($this->transaction)
			$this->rollback ();
	}

	public function commit () {
		if (! $this->transaction)
			throw new sql_Exception ("Called commit() while not in transaction.");
		$this->transaction = false;
		return $this->query ("COMMIT");
	}

	public function in_transaction () {
		return $this->transaction;
	}

	// -------------------------------------------------------- extra update stuff
	
	public function truncate ($table_quotef) {
		$args = func_get_args ();
		$table = $this->quotef_array ($args);
		$this->query ('TRUNCATE %l', $table);
	}

	// -------------------------------------------------------- bulk copy stuff

	public function copy_from ($table_quotef, $columns, $data) {
		$args = func_get_args ();
		$columns = array_pop ($args);
		$data = array_pop ($args);
		$args = $this->quotef_special ($args);
		if (count ($args) != 1) throw new Exception ();
		$table = $args [0];
		return $this->copy_from_real ($table, $columns, $data);
	}

	public function copy_from_real ($table, $column_types, $data) {
		$data_enc = $this->copy_encode ($column_types, $data);
		@pg_copy_from ($this->link, $table, $data_enc);
	}

	public function bulk_insert_real ($table, $columns, $data) {

		// begin transaction
		$my_transaction = ! $this->in_transaction ();
		if ($my_transaction)
			$this->begin ();
		try {

			// create temporary table
			$temp_table = $table . '_sqldb_insert_temp';
			$this->create_temporary_table_real ($temp_table, $columns, $data);
			
			// copy temporary table to real table
			$insert_cols = array ();
			foreach ($columns as $col_name => $col_type)
				$insert_cols [] = $this->quotef ('%z', $col_name);
			$this->query (
				'	INSERT INTO %z (%l)
					SELECT * FROM %z
				',	$table, implode (', ', $insert_cols),
					$temp_table);

			// commit
			if ($my_transaction)
				$this->commit ();
		
		} catch (Exception $e) {
			if ($my_transaction)
				$this->auto_rollback ();
			throw $e;
		} 
	}

	public function type_for_fmt ($fmt) {
		return sql_PostgreSqlDb::$types_for_fmt [$fmt];
	}

	public function types_for_fmt () {
		return sql_PostgreSqlDb::$types_for_fmt;
	}

	private static $types_for_fmt = array (		
		'b' => 'boolean', 'B' => 'boolean NOT NULL',
		'd' => 'date', 'D' => 'date NOT NULL',
		'f' => 'real NOT NULL', 'F' => 'real',
		'i' => 'integer', 'I' => 'integer NOT NULL',
		'r' => 'text', 'R' => 'text NOT NULL',
		's' => 'text', 'S' => 'text NOT NULL',
		't' => 'timestamp with time zone', 'T' => 'timestamp with time zone NOT NULL',
		'x' => 'bytea', 'X' => 'bytea NOT NULL');
	
	public function bulk_insert_table_real ($table, $column_types, $data) {

		// process data
		$data = $this->copy_encode ($column_types, $data);

		// do copy
		pg_copy_from ($this->link, $table, $data);
	}

	public function copy_from_s ($table_name, $data) {
		$rows = array ();
		foreach ($data as $datum) {
			if (is_null ($datum)) {
				$rows [] = "\\N\n";
			} else {
				$datum = str_replace (
					array ("\\", "\010", "\014", "\012", "\015", "\011", "\013"),
					array ('\\\\', '\\b', '\\f', '\\n', '\\r', '\\t', '\\v'),
					$datum);
				$rows [] = sprintf ("%s\n", $datum);
			} 
		}
		pg_copy_from ($this->link, $table_name, $rows);
	}

	/**
	 * This encodes a dataset 
	 */
	public function copy_encode ($column_types, $data) {
		$ret = array ();
		foreach ($data as $row) {
			$i = 0;
			$first = true;
			$str = '';
			foreach ($column_types as $column_type) {
				if (! $first) $str .= "\t"; $first = false;
				$datum = $row [$i++];
				if (is_null ($datum)) {
					$str .= '\\N';
				} else {
					$fn = 'quote_internal_' . $column_type;
					$str .= $this->copy_quote_specials ($this->$fn ($datum));
				}
			}
			$ret [] = $str;
		}
		return $ret;
	}

	public function copy_quote_specials ($str) {
		return str_replace (
			array ("\\", "\010", "\014", "\012", "\015", "\011", "\013"),
			array ('\\\\', '\\b', '\\f', '\\n', '\\r', '\\t', '\\v'),
			$str);
	}

	// -------------------------------------------------------- data definition

	public function create_temporary_table ($table_quotef, $columns, $data = null) {
		
		// process args
		$args = func_get_args ();
		$data = array_pop ($args);
		$columns = sql_AbstractDb::bulk_columns_parse (array_pop ($args));
		$args = $this->quotef_special ($args);
		if (count ($args) != 1) throw new sql_Exception ();
		list ($table) = $args;

		// and delegate
		return $this->create_temporary_table_real ($table, $columns, $data);
	}

	public function create_temporary_table_real ($table, $columns, $data = null) {
		$types_for_fmt = $this->types_for_fmt ();

		// create temporary table
		$create_cols = array ();
		foreach ($columns as $col_name => $col_type)
			$create_cols [] = $this->quotef ('%z %l',
				$col_name, $types_for_fmt [$col_type]);
		$this->query (
			'	CREATE TEMPORARY TABLE %z (%l) ON COMMIT DROP
			',	$table, implode (', ', $create_cols));

		// insert rows to temporary table
		if (! is_null ($data))
			$this->bulk_insert_table_real ($table, array_values ($columns), $data);
	}

	// -------------------------------------------------------- deprecated stuff

	/**
	 * @deprecated
	 */
	public function old_query ($query) {

		if ($this->state != 'connected')
			throw new sql_Exception ('Not connected');
		
		// do the query
		$this->debug ("query", "query $query");
		$result = @pg_query ($this->link, $query);
		if (! $result) {
			$this->debug ("query_error", "error " . $this->last_error ());
			throw new sql_QueryException ($this->last_error ());
		}

		// get affected rows
		$this->last_affected_rows = pg_affected_rows ($result);

		// return
		return $result;
	}

	/**
	 * @deprecated
	 */
	public function lookup ($table, $where) {
		$result = $this->old_query ("SELECT * FROM $table WHERE $where LIMIT 1");
		$row = $result? pg_fetch_object ($result) : false;
		if ($result) pg_free_result ($result);
		return $row;
	}

	/**
	 * @deprecated
	 */
	function select_one ($query, &$row) {
		if (! $result = $this->old_query ($query)) return false;
		$row = pg_fetch_object ($result);
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated 
 	 */
	function select_row ($query, &$row) {
		if (! $result = $this->old_query ($query)) return false;
		$row_temp = pg_fetch_array ($result, null, PGSQL_ASSOC);
		if ($row_temp) $row = new sql_PostgreSqlRow ($row_temp);
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_many ($query, &$rows, $key_name = null) {
		$result = $this->old_query ($query);
		if (! $result) return false;
		$rows = array ();
		while ($row = pg_fetch_object ($result))
			if ($key_name) $rows[$row->$key_name] = $row;
			else $rows[] = $row;
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_rows ($query, &$rows) {
		$result = $this->old_query ($query);
		if (! $result) return false;
		$rows = array ();
		while ($row = pg_fetch_array ($result, null, PGSQL_ASSOC))
			$rows[] = new sql_PostgreSqlRow ($row);
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_rows_i ($query, &$rows, $key_name) {
		$result = $this->old_query ($query);
		if (! $result) return false;
		$rows = array ();
		while ($row = pg_fetch_array ($result, null, PGSQL_ASSOC))
			$rows[$row[$key_name]] = new sql_PostgreSqlRow ($row);
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_value ($query, &$value) {
		$result = $this->old_query ($query);
		$row = pg_fetch_row ($result);
		if ($row) $value = $row[0];
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_rows_s ($query, &$rows, $key_name) {
		$result = $this->old_query ($query);
		$rows = array ();
		while ($row = pg_fetch_array ($result, null, PGSQL_ASSOC))
			$rows[$row[$key_name]] = new sql_PostgreSqlRow ($row);
		pg_free_result ($result);
		return true;
	}

	/**
	 * deprecated
	 */
	function select_values ($query, &$values) {
		$result = $this->old_query ($query);
		$values = array ();
		while ($row = pg_fetch_row ($result))
			if (count ($row) == 2) $values[$row[0]] = $row[1];
			else $values[] = $row[0];
		pg_free_result ($result);
		return true;
	}

	/**
	 * @deprecated
	 */
	function select_count ($query, $default = false) {
		$result = $this->old_query ($query);
		$row = pg_fetch_row ($result);
		pg_free_result ($result);
		return $row? (int) $row[0] : $default;
	}

	/**
	 * @deprecated
	 */
	public function autoRollback () {
		$this->auto_rollback ();
	}

	/**
	 * @deprecated
	 */
	public function select1 () {
		$args = func_get_args ();
		return call_user_func_array (array ($this, "select_1"), $args);
	}

	/**
	 * @deprecated
	 */
	public function update1 () {
		$args = func_get_args ();
		return call_user_func_array (array ($this, "update_1"), $args); 
	}
}

?>