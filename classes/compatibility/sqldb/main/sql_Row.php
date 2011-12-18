<?php

interface sql_Row extends IteratorAggregate {

	public function keys ();
	
	public function b ($field_name);
	public function d ($field_name);
	public function i ($field_name);
	public function r ($field_name);
	public function s ($field_name);
	public function t ($field_name);
	public function x ($field_name);
}

?>
