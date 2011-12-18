<?php

abstract class sql_AbstractRow implements sql_Row {

	protected $row;

	public function __construct ($row) {
		$this->row = $row;
	}
	
	public function __get ($name) {
		$matches = null;
		if (preg_match ('/^([a-z])_([a-z_][a-z0-9_]*)$/', $name, $matches)) {
			return call_user_func (array ($this, $matches [1]), $matches [2]);
		}
	}

	public function getIterator () {
		return new ArrayIterator ($this->row);
	}

	public function keys () {
		return array_keys ($this->row);
	}

	// ------------------------------------------------------------ column access

	public function r ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		return $this->row [$field_name];
	}

	public function s ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_string ($raw_value);
		if (is_string ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not string: ' . $field_name);
		throw new sql_Exception ('Program error');
	}

	public function b ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_boolean ($raw_value);
		if (is_bool ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not boolean: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function f ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_float ($raw_value);
		if (is_float ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not float: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function i ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_integer ($raw_value);
		if (is_int ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not integer: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function d ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_date ($raw_value);
		if (is_object ($value) && $value instanceof ess_Date) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not date: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function t ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_timestamp ($raw_value);
		if (is_int ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not timestamp: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function x ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_binary ($raw_value);
		if (is_string ($value)) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not binary: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	public function m ($field_name) {
		if (! array_key_exists ($field_name, $this->row))
			throw new sql_ColumnNotFoundException ('Column not found: ' . $field_name);
		$raw_value = $this->row [$field_name];
		if (is_null ($raw_value)) return null;
		$value = $this->parse_date ($raw_value);
		if (is_object ($value) && $value instanceof ess_Date) return $value;
		if (is_null ($value)) throw new sql_ColumnTypeException ('Column is not date: ' . $field_name);
		throw new sql_Exception ('Program error'); 
	}

	protected function parse_string ($input) {
		if (is_string ($input)) return $input;
		// return (string) $input; // this line might fix stuff
		return null;
	}

	protected function parse_integer ($input) {
		if (is_string ($input) && preg_match ('/^-?[0-9]+$/', $input)) return (int) $input;
		return null;
	}

	protected abstract function parse_boolean ($input);
	
	protected abstract function parse_timestamp ($input);
	
	protected abstract function parse_date ($input);
	
	protected abstract function parse_binary ($input);
}
?>
