<?php

/**
 * Abstract base implementation of sql_Db.
 */
abstract class sql_AbstractDb implements sql_Db {

	private $debug_logger;
	private $debug_logger_name;
	private $debug_system_name;

	
	private 	$debugQueryTimes = array();
	protected	$recordQueryTimes = false;

	public function __construct ($debug_system_name) {
		$this->debug_logger = new ess_NullDebugLogger ();
		$this->debug_logger_name = null;
		$this->debug_system_name = $debug_system_name;
	}

	public function set_debug_logger (ess_DebugLogger $debug_logger, $name = null) {
		$this->debug_logger = $debug_logger;
		$this->debug_logger_name = $name;
	}

	public function recordQueryTimes ($status) {
			$this->recordQueryTimes = $status;
	}

	public function getQueryTimes () {
			return $this->debugQueryTimes;
	}

	// -------------------------------------------------------- quotef implementation

	public function quotef ($format_etc) {
		$args = func_get_args ();
		$params = $args;
		$format = array_shift ($params);
		if (count ($params) != sf_num_percents ($format))
			throw new sql_Exception ("Parameter count doesn't match format string");
		return $this->quotef_real ($format, $params);
	}

	public function quotef_array ($params) {
		$format = array_shift ($params);
		return $this->quotef_real ($format, $params);
	}

	private function quotef_consume (&$args) {
		$format = array_shift ($args);
		$params = array_slice ($args, 0, sf_num_percents ($format), $args);
		$args = array_slice ($args, count ($params));
		return $this->quotef_real ($format, $params);
	}

	private function quotef_real ($format, $args) {
		$ret = "";
		$pos0 = 0;
		$i = 0;
		while (true) {
			$pos1 = strpos ($format, "%", $pos0);
			if ($pos1 === false)
				return $ret . substr ($format, $pos0);
			if ($pos1 + 1 >= strlen ($format))
				throw new sql_Exception ("Invalid format string");
			$ret .= substr ($format, $pos0, $pos1 - $pos0);
			$ch = substr ($format, $pos1 + 1, 1);
			switch ($ch) {

				// boolean
				case 'b': $ret .= $this->b ($args [$i++]); break;
				case 'B': $ret .= $this->bn ($args [$i++]); break;

				// date
				case 'd': $ret .= $this->d ($args [$i++]); break;
				case 'D': $ret .= $this->dn ($args [$i++]); break;

				// integer 
				case 'i': $ret .= $this->i ($args [$i++]); break;
				case 'I': $ret .= $this->in ($args [$i++]); break;

				// string literal
				case 'l': $ret .= $args [$i++]; break;

				// raw
				case 'r': $ret .= $this->r ($args [$i++]); break;
				case 'R': $ret .= $this->rn ($args [$i++]); break;

				// string/text
				case 's': $ret .= $this->s ($args [$i++]); break;
				case 'S': $ret .= $this->sn ($args [$i++]); break;

				// timestamp
				case 't': $ret .= $this->t ($args [$i++]); break;
				case 'T': $ret .= $this->tn ($args [$i++]); break;

				// binary
				case 'x': $ret .= $this->x ($args [$i++]); break;
				case 'X': $ret .= $this->xn ($args [$i++]); break;

				// identifier
				case 'z': $ret .= $this->z ($args [$i++]); break;

				// literal percent
				case '%': $ret .= "%"; break;

				// unrecognised
				default:  dump($format); throw new sql_Exception ("Invalid format string");
			}
			$pos0 = $pos1 + 2;
		}
	}

	/**
	 * Calls quotef repeatedly consuming members of the passed array, returning the results in an array.
	 * 
	 * @param array $args The format strings followed by their parameters.
	 * @return array The resulting strings. 
	 */
	protected function quotef_special ($args) {
		$ret = array ();
		for ($i = 0; $i < count ($args); ) {
			$format = $args [$i++];
			$params = array_slice ($args, $i, sf_num_percents ($format));
			$i += count ($params);
			array_unshift ($params, $format);
			$ret[] = $this->quotef_array ($params);
		}
		return $ret;
	}

	protected function debug ($subsystem, $message) {
		if ($this->recordQueryTimes)
		$this->debug_logger->add (
			$this->debug_logger_name,
			$this->debug_system_name,
			$subsystem,
			$message);
	}
	
	protected function addQueryTime ($query, $time) {
		$this->debugQueryTimes[] = array(	"query"=>$query, 
											"time"=>$time
										);
	}

	protected abstract function get_id_preg ();

	// -------------------------------------------------------- front-end quoting functions

	public function r ($s) {
		if (is_string ($s)) return $this->quote_raw ($s);
		if (is_int ($s)) return $this->quote_raw ((string) $s);
		if (is_array ($s)) {
			$a = array ();
			foreach ($s as $s0)
				$a[] = $this->s ($s0);
			return sf ('(%s)', implode (', ', $a));
		}
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($s) . ', expected string');
	}

	public function rn ($s) {
		if (is_null ($s)) return 'NULL';
		return $this->r ($s);
	}

	public function s ($s) {
		if (is_string ($s)) return $this->quote_string ($s);
		if (is_int ($s)) return $this->quote_string ((string) $s);
		if (is_array ($s)) {
			$a = array ();
			foreach ($s as $s0)
				$a[] = $this->s ($s0);
			return sf ('(%s)', implode (', ', $a));
		}
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($s) . ', expected string');
	}

	public function sn ($s) {
		if (is_null ($s)) return 'NULL';
		return $this->s ($s);
	}

	public function i ($i) {
		if (is_int ($i)) return $this->quote_integer ($i);
		if (is_string ($i) && preg_match ("/^-?[0-9]+$/", $i)) return $this->quote_integer ((int) $i);
		if (is_array ($i)) {
			$a = array ();
			foreach ($i as $i0)
				$a[] = $this->i ($i0);
			return sf ('(%s)', implode (', ', $a));
		}
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($i) . ', expected int');
	}

	public function in ($i) {
		if (is_null ($i)) return 'NULL';
		return $this->i ($i);
	}
	
	public function b ($b) {
		if (is_bool ($b)) return $this->quote_boolean ($b);
		if (is_string ($b) && 0 == strcmp ($b, 'true')) return $this->quote_boolean (true);
		if (is_string ($b) && 0 == strcmp ($b, 'false')) return $this->quote_boolean (false);
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($b) . ', expected bool');
	}

	public function bn ($i) {
		if (is_null ($i)) return 'NULL';
		return $this->b ($i);
	}
	
	public function t ($d) {
		if (is_int ($d)) return $this->quote_timestamp ($d);
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($d) . ', expected int');
	}

	public function tn ($d) {
		if (is_null ($d)) return 'NULL';
		return $this->t ($d);
	}

	public function d ($m) {
		if (is_object ($m) && $m instanceof ess_Date) return $this->quote_date ($m);
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($m) . ', expected ess_Date');
	}

	public function dn ($m) {
		if (is_null ($m)) return 'NULL';
		return $this->d ($m);
	}

	public function x ($x) {
		if (is_string ($x)) return $this->quote_binary ($x);
		throw new sql_Exception ('Don\'t know what to do with: ' . gettype ($x) . ', expected string');
	}
	
	public function xn ($x) {
		if (is_null ($x)) return 'NULL';
		return $this->x ($x);
	}

	public function z ($z) {
		return $this->quote_identifier ($z);
	}

	// -------------------------------------------------------- back-end quoting functions

	protected abstract function quote_raw ($input);
	protected abstract function quote_string ($input);
	protected abstract function quote_integer ($input);
	protected abstract function quote_boolean ($input);
	protected abstract function quote_timestamp ($input);
	protected abstract function quote_date (ess_Date $input);
	protected abstract function quote_binary ($input);

	// -------------------------------------------------------- general query

	public function query ($query_etc) {

		// get args
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// delegate
		return call_user_func (array ($this, 'query_real'), $query);
	}

	public abstract function query_real ($query);

	// -------------------------------------------------------- select queries

	public function select ($query_etc) {
		
		$args = func_get_args ();
		$query = $this->quotef_array ($args);
		
		return $this->select_real ($query);
	}

	public function select_real ($query) {
		
		$results = $this->query ("%l", $query);
		
		// TEMPORARY due to issue with sybase thing:
		if (count ($results) == 0)
			return array ();

		// check there was exactly one resultset		
		if (count ($results) != 1) {
			throw new sql_Exception ('Query should return one resultset in select()');
			
		}

		return $results [0];
	}

	public function select_1 ($query_etc) {
		
		$args = func_get_args ();
		$rows = call_user_func_array (array ($this, 'select'), $args);

		if (count ($rows) == 1)
			return $rows [0];
		if (count ($rows) == 0)
			return null;
		throw new sql_Exception ('Multiple rows in select_1()');
	}

	public function exists ($from_etc, $where_etc = null) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);

		// work out the calling convention
		switch (count ($args)) {
			case 1: list ($from, $where) = array ($args [0], '1=1'); break;
			case 2: list ($from, $where) = $args; break;
			default: throw new sql_Exception ('Invalid arguments');
		}

		/// and delegate
		return $this->exists_real ($from, $where);
	}

	public function exists_real ($from, $where) {

		// perform the query
		$row = $this->select_1 ('
				SELECT CASE WHEN EXISTS (
					SELECT *
					FROM %l
					WHERE %l
				) THEN %b ELSE %b END AS %z
			',	$from,
				$where,
				true,
				false,
				'exists');

		// and return
		return $row->b_exists;
	}

	public function count ($from_etc, $where_etc = null) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		if (count ($args) == 2)
			list ($from, $where) = $args;
		else list ($from, $where) = array ($args [0], '1=1');

		// and delegate
		return $this->count_real ($from, $where);
	}

	public function count_real ($from, $where) {

		// perform the query
		$row = $this->select_1 ('
				SELECT count(*) AS count
				FROM %l
				WHERE %l
			',	$from,
				$where);

		// and return
		return $row->i_count;
	}

	public function select_by_i ($query_etc, $field_name_etc) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($query, $field_name) = $args;
		
		// perform the select
		$rows = $this->select ('%l', $query);

		// reindex the array
		$ret = array ();
		foreach ($rows as $row)
			$ret [$row->i ($field_name)] = $row;

		return $ret;
	}

	public function select_i_by_i ($query_etc, $key_name_etc, $field_name_etc) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($query, $key_name, $field_name) = $args;
		
		// perform the select
		$rows = $this->select ('%l', $query);

		// reindex the array
		$ret = array ();
		foreach ($rows as $row)
			$ret [$row->i ($key_name)] = $row->i ($field_name);

		return $ret;
	}

	public function select_i_by_s ($query_etc, $key_name_etc, $field_name_etc) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($query, $key_name, $field_name) = $args;
		
		// perform the select
		$rows = $this->select ('%l', $query);

		// reindex the array
		$ret = array ();
		foreach ($rows as $row)
			$ret [$row->s ($key_name)] = $row->i ($field_name);

		return $ret;
	}

	public function select_s_by_i ($query_etc, $key_name_etc, $field_name_etc) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($query, $key_name, $field_name) = $args;
		
		// perform the select
		$rows = $this->select ('%l', $query);

		// reindex the array
		$ret = array ();
		foreach ($rows as $row)
			$ret [$row->i ($key_name)] = $row->s ($field_name);

		return $ret;
	}

	public function select_by_s ($query_etc, $field_name_etc) {

		// use quotef_special to evaluate the format strings
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($query, $field_name) = $args;
		
		// perform the select
		$rows = $this->select ('%l', $query);
		
		// reindex the array
		$ret = array ();
		foreach ($rows as $row)
			$ret [$row->s ($field_name)] = $row;

		return $ret;
	}

	public function select_i ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$rows = $this->select ('%l', $query);
		
		// get the values
		if (count ($rows) == 0) return array ();
		$keys = $rows [0]->keys ();
		$key = $keys [0];
		foreach ($rows as &$row)
			$row = $row->i ($key);
		return $rows;
	}

	public function select_s ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$rows = $this->select ('%l', $query);
		
		// get the values
		if (count ($rows) == 0) return array ();
		$keys = $rows [0]->keys ();
		$key = $keys [0];
		foreach ($rows as &$row)
			$row = $row->s ($key);
		return $rows;
	}

	public function select_1_i ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$row = $this->select_1 ('%l', $query);
		if (! $row) return null;
		$keys = $row->keys ();
		return $row->i ($keys [0]);
	}

	public function select_1_b ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$row = $this->select_1 ('%l', $query);
		if (! $row) return null;
		$keys = $row->keys ();
		return $row->b ($keys [0]);
	}

	public function select_1_s ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$row = $this->select_1 ('%l', $query);
		if (! $row) return null;
		$keys = $row->keys ();
		return $row->s ($keys [0]);
	}

	public function select_1_t ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// and delegate
		return $this->select_1_t_real ($query);
	}

	public function select_1_t_real ($query) {

		// perform the query
		$row = $this->select_1 (
			'	SELECT (%l) AS value
			',	$query);

		// and return
		return $row->t_value;
	}

	/**
	 * Runs a query which returns a single row with a single boolean value,
	 * and returns this directly.
	 */
	public function select_b ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$row = $this->select (
			'	SELECT (%l) AS value
			',	$query);
		return $row->b_value;
	}

	public function select_d ($query_etc) {

		// use quotef to evaluate the format string
		$args = func_get_args ();
		$query = $this->quotef_array ($args);

		// perform the query
		$row = $this->select ('
				SELECT (%l) AS value
			',	$query);
		return $row->t_value;
	}

	public function get ($from_etc, $where_etc) {

		// evaluate args, taking note of special case where $where is an integer
		$args = func_get_args ();
		$from = $this->quotef_consume ($args);
		if (count ($args) == 1 and is_int ($args [0]))
			$where = $this->quotef ('id = %i', $args [0]);
		else $where = $this->quotef_consume ($args);

		// perform the query
		return $this->select_1 ('SELECT * FROM %l WHERE %l', $from, $where);
	}

	// -------------------------------------------------------- update and delete queries

	public function make_insert_query ($into_etc, $sets_etc) {

		// work out the calling convention
		if (func_num_args () == 2 && is_array ($sets_etc)) {
			$into = $into_etc;
			$row = $sets_etc;
		} else {
			$args = func_get_args ();
			$sets = $this->quotef_special ($args);
			$into = array_shift ($sets);
			$row = array ();
			$id_preg = $this->get_id_preg ();
			foreach ($sets as $set) {
				if (! preg_match ('/^ \s* (' . $id_preg . ') \s* = \s* (.+) \s* $/xsi', $set, $matches))
					throw new sql_Exception ("Invalid param: $set");
				$row [$matches [1]] = $matches [2]; 
			}
		}
		
		// and delegate
		return $this->make_insert_query_real ($into, $row);
	}
	
	public function make_insert_query_real ($into, $sets) {
		return $this->quotef ('
				INSERT INTO %l (%l) VALUES (%l)
			',	$into,
				implode (', ', array_keys ($sets)),
				implode (', ', $sets));
	}
	
	public function insert ($into_etc, $sets_etc) {

		// generate the query
		$args = func_get_args ();
		$query = call_user_func_array (array ($this, 'make_insert_query'), $args);
			
		// perform it
		$this->query ('%l', $query);

		return true;
	}

	public function make_update_query ($update_etc, $where_etc, $sets_etc) {

		// work out the calling convention
		if (func_num_args () == 3 && is_array ($sets_etc)) {

			// this is the old (deprecated) convention
			$update = $update_etc;
			$where = $where_etc;
			$sets = array ();
			foreach ($sets_etc as $col => $value) $sets[] = "$col = $value";
			if (count ($sets) == 0)
				throw new sql_Exception ('Called make_update_query() with no sets');

		} else {

			// this is the new convention
			$args = func_get_args ();
			$sets = $this->quotef_special ($args);
			$update = array_shift ($sets);
			$where = array_shift ($sets);
			if (count ($sets) == 0) $sets = array ($where);
		}

		// and delegate
		return $this->make_update_query_real ($update, $where, $sets);
	}

	public function make_update_query_real ($update, $where, $sets) {
		return $this->quotef ('
				UPDATE %l
				SET %l
				WHERE %l
			',	$update,
				implode (', ', $sets),
				$where);
	}
	
	public function update ($update_quotef, $where_quotef, $sets_quotef) {

		// generate the query
		$args = func_get_args ();
		$query = call_user_func_array (array ($this, 'make_update_query'), $args);

		// perform it
		$this->query ('%l', $query);
		
		return true;
	}

	public function update_array ($array) {
		return call_user_func_array (array ($this, 'update'), $array);
	}

	public function update_real ($update, $where, $sets) {

		// generate the query
		$args = func_get_args ();
		$query = call_user_func_array (array ($this, 'make_update_query_real'), $args);

		// perform it
		$this->query ('%l', $query);
		
		return true;
	}

	public function update_1 ($table_quotef, $where_quotef, $values_quotef_etc) {

		// call update
		$args = func_get_args ();
		$ret = call_user_func_array (array ($this, 'update'), $args);

		// ensure affected row count is correct
		if ($this->affected_rows () == 0)
			throw new sql_Exception ('No rows affected in update_1()');
		if ($this->affected_rows () > 1) 
			throw new sql_Exception ('Multiple rows affected in update_1()');

		return true;
	}

	public function make_delete_query ($from_quotef, $where_quotef) {

		// get args
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($from, $where) = $args;

		// and delegate
		return $this->make_delete_query_real ($from, $where);
	}

	public function make_delete_query_real ($from, $where) {

		// perform query
		return $this->quotef (
			'	DELETE FROM %l
				WHERE %l
			',	$from,
				$where);
	}

	public function delete ($from_etc, $where_etc) {

		// get args
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		list ($from, $where) = $args;

		// perform query
		$this->query ('
				DELETE FROM %l
				WHERE %l
			',	$from,
				$where);

		return true;
	}

	public function delete_1 ($from_etc, $where_etc) {

		// call delete
		$args = func_get_args ();
		$ret = call_user_func_array (array ($this, 'delete'), $args);

		// ensure affected row count is correct
		if ($this->affected_rows () == 0)
			throw new sql_Exception ('No rows affected in delete_1()');
		if ($this->affected_rows () > 1) 
			throw new sql_Exception ('Multiple rows affected in delete_1()');

		return true;
	}

	public function insert_if_not_exists ($table_etc, $where_etc, $values_etc) {

		// get args
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		$table = $args [0];
		$where = $args [1];

		// see if row already exists
		if ($this->exists ('%l', $table, '%l', $where))
			return;

		// insert it
		call_user_func_array (array ($this, 'insert'), $args);
	}
	
	public function insert_or_update_1 ($table_etc, $where_etc, $values_etc) {

		// get args
		  $args0 = func_get_args ();
		  $args1 = $this->quotef_special ($args0);
		  $table = $args1 [0];
		  $where = $args1 [1];
		  
		// see if row already exists
		$exists = $this->exists ('%l', $table, '%l', $where);

		// insert or update it
		call_user_func_array (array ($this, $exists? 'update_1' : 'insert'), $args0);
	}

	public function delete_and_insert ($table_etc, $where_etc, $values_etc) {

		// get args
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		$table = $args [0];
		$where = $args [1];

		// delete existing row
		$this->delete ('%l', $table, '%l', $where);
		if ($this->affected_rows () > 1)
			throw new sql_Exception ('Multiple rows affected in delete_and_insert()');

		// insert new row
		call_user_func_array (array ($this, 'insert'), $args);
	}

	// -------------------------------------------------------- data definition

	public function create_table ($table_quotef, $columns, $data = null) {
		
		// process args
		$args = func_get_args ();
		$data = array_pop ($args);
		$columns = sql_AbstractDb::bulk_columns_parse (array_pop ($args));
		$args = $this->quotef_special ($args);
		if (count ($args) != 1) throw new sql_Exception ();
		list ($table) = $args;

		// and delegate
		return $this->create_table_real ($table, $columns, $data);
	}

	public function create_table_real ($table, $columns, $data = null) {
		$types_for_fmt = $this->types_for_fmt ();

		// create temporary table
		$create_cols = array ();
		foreach ($columns as $col_name => $col_type)
			$create_cols [] = $this->quotef ('%z %l',
				$col_name, $types_for_fmt [$col_type]);
		$this->query (
			'	CREATE TABLE %z (%l)
			',	$table, implode (', ', $create_cols));

		// insert rows to temporary table
		if (! is_null ($data))
			$this->bulk_insert_table_real ($table, array_values ($columns), $data);
	}

	public function drop_table ($table_quotef) {
		
		// process args
		$args = func_get_args ();
		$args = $this->quotef_special ($args);
		if (count ($args) != 1) throw new sql_Exception ();
		list ($table) = $args;

		// and delegate
		return $this->drop_table_real ($table);
	}

	public function drop_table_real ($table) {
		$this->query (
			'	DROP TABLE %l
			',	$table);
	}

	// -------------------------------------------------------- bulk inserts

	public function bulk_insert ($table_etc, $columns, $data) {
		$args = func_get_args ();
		$data = array_pop ($args);
		$columns = sql_AbstractDb::bulk_columns_parse (array_pop ($args));
		$table = $this->quotef_array ($args);
		return $this->bulk_insert_real ($table, $columns, $data);
	}

	public function bulk_insert_real ($table, $columns, $data) {

		// process data
		$data = $this->bulk_quote ($columns, $data);

		// create template query
		$quoted_columns = array ();
		foreach ($columns as $col_name => $col_type)
			$quoted_columns [] = $this->z ($col_name);
		$query_prefix = $this->quotef (
			'INSERT INTO %l (%l) VALUES (',
			$table, implode (', ', $quoted_columns));
		$query_suffix = ')';

		// do inserts
		foreach ($data as $datum)
			$this->query_real ($query_prefix . implode (', ', $datum) . $query_suffix);
	}

	public function bulk_insert_table ($table_etc, $column_types, $data) {
		$args = func_get_args ();
		$data = array_pop ($args);
		if (is_string ($column_types)) $column_types = str_to_char_array ($column_types);
		$table = $this->quotef_array ($args);
		return $this->bulk_insert_table_real ($table, $column_types, $data);
	}

	public function bulk_insert_table_real ($table, $column_types, $data) {

		// process data
		$data = $this->bulk_quote ($column_types, $data);

		// create template query
		$query_prefix = $this->quotef (
			'INSERT INTO %l VALUES (',
			$table);
		$query_suffix = ')';

		// do inserts
		foreach ($data as $datum)
			$this->query_real ($query_prefix . implode (', ', $datum) . $query_suffix);
	}

	/**
	 * A fast method for quoting many database values at once. This should be
	 * used instead of quotef where a lot of data is involved as the latter
	 * incurs a large performance penalty.
	 */
	public function bulk_quote ($column_types, $data) {
		$ret = array_fill (0, count ($data), array ());
		$c = 0;
		foreach ($column_types as $column_type) {
			$fn = sql_AbstractDb::$funcs_for_fmt [$column_type];
			$i = 0;
			foreach ($data as $datum)
				$ret [$i++] [] = $this->$fn ($datum [$c]);
			$c++;
		}
		return $ret;
	}

	public function bulk_to_string ($column_types, $rows) {

		$to_string_by_fmt = array (
			'b' => 'to_string_boolean', 'B' => 'to_string_boolean',
			'd' => 'to_string_date', 'D' => 'to_string_data',
			'i' => 'to_string_integer', 'I' => 'to_string_integer',
			'r' => 'to_string_raw', 'R' => 'to_string_raw',
			's' => 'to_string_string', 'S' => 'to_string_string',
			't' => 'to_string_timestamp', 'T' => 'to_string_timestamp');

		$ret = array ();
		foreach ($rows as $row) {
			$temp = array ();
			foreach ($column_types as $i => $column_type) {
				if (is_null ($row [$i])) {
					$temp [] = null;
				} else {
					$fn = $to_string_by_fmt [$column_type];
					$temp [] = $this->$fn ($row [$i]);
				}
			}
			$ret [] = $temp;
		}
		return $ret;
}
	private static $funcs_for_fmt = array (
		'b' => 'b', 'B' => 'bn',
		'd' => 'd', 'D' => 'dn',
		'i' => 'i', 'I' => 'in',
		'r' => 'r', 'R' => 'rn',
		's' => 's', 'S' => 'sn',
		't' => 't', 'T' => 'tn',
		'x' => 'x', 'X' => 'xn');

	/**
	 * Parses a column spec string into array form. If given an array, will
	 * simply return that. If given a string it expects a whitespace-separated
	 * series of type and name pairs, where the type is represented by the 
	 * character which follows '%' in quotef for the same. The returned array
	 * has column names as keys and type characters as values.
	 */
	public static function bulk_columns_parse ($spec) {
		$matches = null;
		
		if (is_array ($spec)) return $spec;
		if (is_string ($spec)) {
			preg_match_all ('/ \\S+ /x', $spec, $matches);
			$tokens = $matches [0];
			if (count ($tokens) % 2 != 0)
				throw new sql_Exception ('Invalid columns spec for bulk_columns_parse');
			$ret = array ();
			for ($i = 0; $i < count ($tokens); $i += 2)
				$ret [$tokens [$i + 1]] = $tokens [$i];
			return $ret;
		}
		throw new sql_Exception ('Wrong type: ' . gettype ($spec));
	}
}

?>
