<?php
/**
 * General utility class representing a single calendar year.
 */
class ess_Year implements ess_DateRange {
	private $year;
	
	public function __construct ($year) {
		if ($year < 0 || $year > 9999)
			throw new Exception ('Invalid date');
		$this->year = (int) $year;
	}
	
	public function year () {
		return $this->year;
	}
	
	public function y () {
		return sprintf ('%04d', $this->year, $this->month);
	}

	public function year () {
		return new ess_Year ($this->year);
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
		return mktimez ($this->year, 1, 1, 0, 0, 0);
	}

	public function local_end_ts () {
		return mktimez ($this->year + 1, 1, 1, 0, 0, 0);
	}

	public function add ($num) {
		return new ess_Year ($this->year + 1);
	}

	public static function from_y ($y) {
		$matches = null;
		if (! preg_match ('/^ (\\d{4}) $/x', $y, $matches))
			throw new Exception ('Invalid year format');
		return new ess_Year ($matches [1]);
	}

	public static function eq ($year0, $year1, $if_null = true) {
		if (is_null ($year0) && is_null ($year1))
			return $if_null;
		if (is_null ($year0) || is_null ($year1))
			return false;
		return
			$year0->year == $year1->year;
	}

	public static function today () {
		return new ess_Year (date ('Y'));
	}

	public static function from_str ($str) {
		$matches = null;

		if (preg_match ('/^ \\s* $/x', $str))
			return null;

		if (preg_match ('/^ \\s* (\\d{4}) \\s* $/x', $str, $matches))
			return new ess_Year ($matches [1]);

		throw new Exception ('Invalid year format');
	}
}

?>
