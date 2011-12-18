<?php

/**
 * Base class for debug logging. The idea is that you pass one to a subsystem
 * and it logs debug messages using it.
 */ 
interface ess_DebugLogger {
	public function add ($name, $system, $subsystem, $message);
}
?>
