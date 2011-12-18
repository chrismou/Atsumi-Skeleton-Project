<?php

class sql_PostgreSqlSyncSource implements sql_SyncSource {

	private $db;
	private $prefix;

	public function __construct (sql_PostgreSqlDb $db, $prefix) {
		$this->db = $db;
		$this->prefix = $prefix;
	}

	public function pending () {
		return $this->db->exists ('%z', $this->prefix . '_entry');
	}

	public function get_pending_sync_table_recs () {
		return $this->db->select (
			'	SELECT DISTINCT sync_table.*
				FROM %z AS sync_entry
				INNER JOIN %z AS sync_table
					ON sync_entry.sync_table_id = sync_table.id
			',	$this->prefix . '_entry',
				$this->prefix . '_table');
	}

	public function get_sync_table_rec ($table) {
		return $this->db->select_1 (
			'	SELECT *
				FROM %z
				WHERE %z = %s
			',	$this->prefix . '_table',
				'table', $table);
	}
	
	public function begin () {
		$this->db->begin ();
	}

	public function commit () {
		$this->db->commit ();
	}

	public function auto_rollback () {
		$this->db->auto_rollback ();
	}

	public function get_source_recs ($sync_table_rec, $max) {
		$sync_table_name = $sync_table_rec->s_table;
		$sync_table_id = $sync_table_rec->i_id;

		return $this->db->select (
			'	SELECT %z.*,
					sync_entry.id AS sync_entry_id,
					sync_entry.row_id AS sync_entry_row_id,
					sync_entry.deleted AS sync_entry_deleted
				FROM %z AS sync_entry
				LEFT JOIN %z ON sync_entry.row_id = %z.id
				WHERE sync_entry.sync_table_id = %i
				ORDER BY deleted DESC, id
				FOR UPDATE OF sync_entry
				LIMIT %i
			',	$sync_table_rec->s_table,
				$this->prefix . '_entry',
				$sync_table_rec->s_table, $sync_table_rec->s_table,
				$sync_table_rec->i_id,
				$max);
	}
	
	public function get_sync_column_recs ($sync_table_rec) {
		return $this->db->select (
				'	SELECT *
					FROM %z AS sync_column
					WHERE sync_table_id = %i
				',	$this->prefix . '_column',
					$sync_table_rec->i_id);
	}

	public function finish ($source_recs) {
		$ids = array ();
		foreach ($source_recs as $source_rec)
			$ids [] = $source_rec->i_sync_entry_id;
		$this->db->delete ('sync_entry', 'id IN %i', $ids);
	}
}
?>
