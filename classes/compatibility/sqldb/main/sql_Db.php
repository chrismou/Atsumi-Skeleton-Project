<?php

// ============================================================ db interface

interface sql_Db {

	// -------------------------------------------------------- escaping functions

	/**
	 * Transforms a string into a query literal.
	 * @param string $val The value to escape.
	 * @return string The escaped and quoted string. 
	 */
	public function s ($val);

	/**
	 * Transforms a string or null into a query literal.
	 * @param string $val The value to escape.
	 * @return string The escaped and quoted string or null representation.
	 */
	public function sn ($val);

	/**
	 * Transforms an integer into a query literal.
	 * @param int $val The value to escape.
	 * @return string The escaped and quoted integer. 
	 */
	public function i ($val);

	/**
	 * Transforms an integer or null into a query literal.
	 * @param string $val The value to escape.
	 * @return string The escaped and quoted integer or null representation.
	 */
	public function in ($val);

	/**
	 * Transforms a boolean into a query literal.
	 * @param string $val The value to escape.
	 * @return string The escaped and quoted boolean.
	 */
	public function b ($val);

	/**
	 * Transforms a unix timestamp into the a timestamp literal.
	 */
	public function d ($val);

	/**
	 * Transforms a string into a binary literal.
	 */
	public function x ($val);

	/**
	 * Escapes an identifier.
	 */
	public function z ($val);
	
	public function quotef ($format_etc);

	// -------------------------------------------------------- transaction stuff

	/**
	 * Returns true if and only if the underlying database supports
	 * transactions.
	 * 
	 * @return boolean
	 */
	public function supports_transactions ();
	
	/**
	 * Returns true if and only if a transaction is in progress. You must always
	 * use the transaction functions and not control transactions manually (or
	 * in stored procedures) for this to work correctly.
	 * 
	 * @return boolean
	 */
	public function in_transaction ();

	/**
	 * Begins a database transaction. Will fail if there is already a
	 * transaction in progress.
	 * 
	 * @throws sql_Exception
	 */
	public function begin ();
	
	/**
	 * Commits a database transaction. Will fail if there is no transaction in
	 * progress.
	 * 
	 * @throws sql_Exception
	 */
	public function commit ();

	/**
	 * Rolls back a database transaction. Will fail if there is no transaction
	 * in progress.
	 * 
	 * @throws sql_Exception
	 */
	public function rollback ();

	/**
	 * Rolls back a database transaction, if there is one in progress. This is
	 * designed for use in catch blocks to ensure a rollback in case of error.
	 * 
	 * @throws sql_Exception
	 */
	public function auto_rollback ();

	// -------------------------------------------------------- general stuff

	/**
	 * Returns the number of rows affected by the last update() or update_1()
	 * call.
	 * 
	 * @return integer The number of rows.
	 */
	public function affected_rows ();

	/**
	 * Performs any query, returning the results as an array of arrays of
	 * sql_Row objects. This allows multiple result sets to be returned.
	 * 
	 * @param string $query_etc The query, in quotef format.
	 */
	public function query ($query_etc);

	// -------------------------------------------------------- select queries

	/**
	 * Performs a query, returning the results as an array of sql_Row objects.
	 * 
	 * @param string $query_etc The query, in quotef format.
	 * @return array The returned rows as an array of sql_Row objects.
	 */
	public function select ($query_etc);

	/**
	 * Performs a query, returning the single result as a sql_Row object. Calls
	 * select internally and throws an exception if it does not return exactly
	 * one row.
	 * 
	 * @param string $query_etc The query, in quotef format.
	 * @return object The row as a sql_Row.
	 */
	public function select_1 ($query_etc);

	/**
	 * Performs a "SELECT EXISTS (SELECT * FROM ... WHERE ...)" query.
	 * 
	 * @param string $from_etc The from clause, in quotef format
	 * @param string $where_etc The where clause, in quotef format, optional
	 * @return boolean The value returned from the query.
	 */
	public function exists ($from_etc, $where_etc = null);

	/**
	 * Performs a "SELECT count (*) FROM ... WHERE ..." query.
	 *
	 * @param string $from_etc The from clause, in quotef format.
	 * @param string $where_etc The where clause, in quotef format.
	 * @return int The value returned from the query.
	 */
	public function count ($from_etc, $where_etc = null);

	public function select_by_i ($query_quotef, $field_name);

	public function select_by_s ($query_quotef, $field_name);

	public function select_i ($query_quotef);

	public function select_s ($query_quotef);

	/**
	 * Performs a query and returns its single integer result.
	 * 
	 * @param string $query_etc The query clause, in quotef format.
	 * @return integer The value returned from the query.
	 */
	public function select_1_i ($query_quotef);

	/**
	 * Performs a query and returns its single string result.
	 * 
	 * @param string $query_etc The query clause, in quotef format.
	 * @return string The value returned from the query.
	 */
	public function select_1_s ($query_quotef);

	/**
	 * Runs a query which returns a single row with a single timestamp value,
	 * and returns this directly.
	 */
	public function select_1_t ($query_quotef);

	/**
	 * Runs a query which returns a single row with a single timestamp value,
	 * and returns this directly.
	 */
	public function select_1_t_real ($query);
 
	/**
	 * Performs a query and returns its single boolean result.
	 * 
	 * @param string $query_etc The query clause, in quotef format.
	 * @return boolean The value returned from the query.
	 */
	public function select_b ($query_etc);

	/**
	 * Performs a query and returns its single timestamp result.
	 * 
	 * @param string $query_quotef The query clause, in quotef format.
	 * @return integer The value returned from the query as a unix timestamp
	 * value.
	 */
	public function select_d ($query_quotef);

	/**
	 * Performs a "SELECT * FROM ... WHERE ..." query and returns its
	 * single result as a sql_Row object.
	 * 
	 * Can also take an integer for the where clause, which is intepreted as
	 * the value of the column "id".
	 * 
	 * @param string $from_quotef The FROM clause, in quotef format.
	 * @param string|integer $where_quotef The WHERE clause, in quotef format, or
	 * the integer id.
	 * @return object The selected row as a sql_Row object.
	 */
	public function get ($from_quotef, $where_quotef);

	// -------------------------------------------------------- insert/update/delete queries

	/**
	 * Performs an insert query. The parameters consist of the table name, in
	 * quotef format, followed by a series of update-like expressions, in quotef
	 * format. For example:
	 * 
	 * <pre>
	 * $db->insert (
	 *     "%z.%z", $schema_name, $table_name,
	 * 	   "id = %i", $id,
	 * 	   "insert_time = now ()");
	 * </pre>
	 * 
	 * @param string $into_quotef The into clause, in quotef format.
	 * @param string $values_quotef_etc The values clauses, in quotef format.
	 */
	public function insert ($into_quotef, $values_quotef_etc);

	/**
	 * Performs an update query. The parameters consist of the table name, in
	 * quotef format, the where clause, in quotef format, and a series of
	 * set expressions, in quotef format. For example:
	 * 
	 * <pre>
	 * $db->update (
	 *     "%z.%z", $schema_name, $table_name, // table name
	 *     "id = %i", $id, // where clause
	 *     "update_time = now ()");
	 * </pre>
	 * 
	 * @param string $table_quotef The table name, in quotef format.
	 * @param string $where_quotef The where clause, in quotef format.
	 * @param string $sets_quotef_etc The parts of the set clause, in quotef format.
	 */
	public function update ($table_quotef, $where_quotef, $sets_quotef_etc);

	/**
	 * Calls update and ensures that the number of affected rows is exactly 1.
	 * 
	 * @param string $table_quotef The table name, in quotef format.
	 * @param string $where_quotef The where clause, in quotef format.
	 * @param string $sets_quotef_etc The parts of the set clause, in quotef format.
	 */
	public function update_1 ($table_quotef, $where_quotef, $sets_quotef_etc);

	/**
	 * Performs a delete query.
	 *
	 * @param string $from_quotef The from clause, in quotef format.
	 * @param string $where_quotef The where clause, in quotef format.
	 */
	public function delete ($from_quotef, $where_quotef);

	/**
	 * Performs a delete query and ensures that the number of affected rows is exactly 1.
	 * 
	 * @param string $from_etc The from clause, in quotef format.
	 * @param string $where_etc The where clause, in quotef format.
	 */
	public function delete_1 ($from_quotef, $where_quotef);

	public function insert_if_not_exists ($table_quotef, $where_quotef, $values_quotef);
	
	public function insert_or_update_1 ($table_quotef, $where_quotef, $values_quotef);

	public function delete_and_insert ($table_quotef, $where_quotef, $values_quotef);

	// ------------------------------------------------------------ bulk insert

	/**
	 * This inserts multiple rows and should always be much faster than
	 * calling insert many times. Where possible it should use any underlying
	 * database features to further increase speed.
	 * 
	 * If inserting whole table rows use bulk_insert_table() which may be
	 * faster.
	 */
	public function bulk_insert ($table_quotef, $columns, $data);

	public function bulk_insert_real ($table, $columns, $data);
	
	/**
	 * This inserts multiple rows and should always be much faster than
	 * calling insert many times. Where possible it should use any underlying
	 * database features to further increase speed.
	 */
	public function bulk_insert_table ($table_quotef, $column_types, $data);

	public function bulk_insert_table_real ($table, $column_types, $data);

	// ------------------------------------------------------------ data definition

	public function create_table ($table_quotef, $columns, $data = null);

	public function create_table_real ($table, $columns, $data = null);

	public function drop_table ($table_quotef);

	public function drop_table_real ($table);
}

?>
