<?php
class ess_SimpleTsRange implements ess_TsRange {
	private $start_ts;
	private $end_ts;

	public function __construct ($start_ts, $end_ts) {
		$this->start_ts = (int) $start_ts;
		$this->end_ts = (int) $end_ts;
		if ($this->start_ts < 0 || $this->end_ts < 0 || $this->start_ts < $this->end_ts)
			throw new Exception ('Invalid timestamp range');
	}

	public function start_ts () {
		return $this->start_ts;
	}
	
	public function end_ts () {
		return $this->end_ts;
	}
}
?>
