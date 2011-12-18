<?php

/**
 * Adaptor for DebugLogger which modifies the name.
 */
class ess_DebugLoggerNameAdaptor extends ess_DebugLogger {
	private $target;
	private $name;

	public function __construct ($target, $name) {
		parent::__construct ();
		$this->target = $target;
		$this->name = $name;
	}
	
	public function add ($name, $system, $subsystem, $message) {
		$this->target->add ($this->name, $system, $subsystem, $message);
	}
}
?>
