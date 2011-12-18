<?php

/**
 * Represents a range of calendar dates.
 */
interface ess_DateRange {

	/**
	 * Returns the first ess_Date included in this date range.
	 */
	function start_date ();
	
	/**
	 * Returns the first ess_date not included at the end of this date range.
	 */
	function end_date ();
	
	/**
	 * Returns an ess_TsRange representing the start and end of this date range
	 * in local time.
	 */
	function local ();
	
	/**
	 * Returns a unix timestamp representing the start of this date range in
	 * local time.
	 */
	function local_start_ts ();
	
	/**
	 * Returns a unix timestamp representing the end of this date range in
	 * local time.
	 */
	function local_end_ts ();
}

?>
