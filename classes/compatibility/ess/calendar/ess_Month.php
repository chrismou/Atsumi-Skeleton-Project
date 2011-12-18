<?php
/**
 * General utility class representing a single calendar month.
 */
class ess_Month implements ess_DateRange {
	private $year;
	private $month;
	
	public function __construct ($year, $month) {
		if ($month < 1 || $month > 12)
			throw new Exception ('Invalid month');
		$this->year = (int) $year;
		$this->month = (int) $month;
	}
	
	public function year () {
		return $this->year;
	}
	
	public function month () {
		return $this->month;
	}
	
	public function ym () {
		return sprintf ('%04d-%02d', $this->year, $this->month);
	}

	public function year_obj () {
		return new ess_Year ($this->year);
	}

	public function date_obj ($date) {
		return new ess_Date ($this->year, $this->month, $date);
	}

	public function start_date () {
		return new ess_Date ($this->year, $this->month, 1);
	}

	public function end_date () {
		return $this->add (1)->start_date ();
	}

	public function local () {
		return new ess_SimpleTsRange ($this->local_start_ts (), $this->local_end_ts ());
	}

	public function local_start_ts () {
		return mktimez ($this->year, $this->month, 1, 0, 0, 0);
	}

	public function local_end_ts () {
		return mktimez ($this->year, $this->month + 1, 1, 0, 0, 0);
	}

	public function add ($num) {
		$t = gmmktimez ($this->year, $this->month + $num, 1, 0, 0, 0);
		return new ess_Month (
	 		gmdate ('Y', $t),
			gmdate ('m', $t));
	}

	public static function from_ym ($ym) {
		$matches = null;
		if (! preg_match ('/^ (\\d{4}) - (\\d{2}) $/x', $ym, $matches))
			throw new Exception ('Invalid month format');
		return new ess_Month ($matches [1], $matches [2]); 
	}

	public static function eq ($month0, $month1, $if_null = true) {
		if (is_null ($month0) && is_null ($month1))
			return $if_null;
		if (is_null ($month0) || is_null ($month1))
			return false;
		return
			$month0->year == $month1->year &&
			$month0->month == $month1->month;
	}

	public static function today () {
		list ($y, $m) = explode ('-', date ('Y-m'));
		return new ess_Month ($y, $m);
	}

	public static function from_str ($str) {
		$matches = null;

		if (preg_match ('/^ \\s* $/x', $str))
			return null;

		if (preg_match ('/^ \\s* (\\d{4}) - (\\d{1,2}) \\s* $/x', $str, $matches))
			return new ess_Month ($matches [1], $matches [2]);

		if (preg_match ('/^ \\s* (\\d{1,2}) [\\\\\\/-] (\\d{1,2}) \\s* $/x', $str, $matches))
			return new ess_Month ($matches [2] + 2000, $matches [1]);

		if (preg_match ('/^ \\s* (\\d{1,2}) \\/ (\\d{4}) \\s* $/x', $str, $matches))
			return new ess_Month ($matches [2], $matches [1]);

		throw new Exception ('Invalid month format');
	}
}

?>
