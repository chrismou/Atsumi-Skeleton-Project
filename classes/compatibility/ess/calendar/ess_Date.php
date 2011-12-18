<?php
/**
 * General utility class representing a single calendar date. When combined
 * with a time zone this can be mapped to and from a timestamp value.
 */
class ess_Date {

	private $year;
	private $month;
	private $day;

	public function __construct ($year, $month, $day) {
		if (! checkdatez ($year, $month, $day))
			throw new Exception ('Invalid date');
		$this->year = (int) $year;
		$this->month = (int) $month;
		$this->day = (int) $day;
	}

	public function year () {
		return $this->year;
	}
	
	public function month () {
		return $this->month;
	} 
	
	public function day () {
		return $this->day;
	}

	public function day_of_week () {
		return (int) gmdate ('w', mktimez ($this->year, $this->month, $this->day, 0, 0, 0));
	}

	public function year_obj () {
		return new ess_Year ($this->year);
	}
	
	public function month_obj () {
		return new ess_Month ($this->year, $this->month);
	}
	
	public function ymd () {
		return sprintf ('%04d-%02d-%02d', $this->year, $this->month, $this->day);
	}

	public function local () {
		return new ess_SimpleTsRange ($this->local_start_ts (), $this->local_end_ts ());
	}

	public function local_ts ($hour, $minute, $second) {
		return mktimez ($this->year, $this->month, $this->day, $hour, $minute, $second);
	}

	public function local_start_ts () {
		return mktimez ($this->year, $this->month, $this->day, 0, 0, 0);
	}

	public function local_end_ts () {
		return mktimez ($this->year, $this->month, $this->day + 1, 0, 0, 0);
	}

	public function add ($num) {
		$t = gmmktimez ($this->year, $this->month, $this->day + $num, 0, 0, 0);
		return new ess_Date (
	 		gmdate ('Y', $t),
			gmdate ('m', $t),
			gmdate ('d', $t)); 
	}

	public static function from_ymd ($ymd) {
		$matches = null;
		if (! preg_match ('/^ (\\d{4}) - (\\d{2}) - (\\d{2}) $/x', $ymd, $matches)) {
			throw new Exception ('Invalid date format');
		}
		return new ess_Date ($matches [1], $matches [2], $matches [3]); 
	}

	public static function from_local_ts ($ts) {
		return ess_Date::from_ymd (date ('Y-m-d', $ts));
	}

	public static function eq ($date0, $date1, $if_null = true) {
		if (is_null ($date0) && is_null ($date1))
			return $if_null;
		if (is_null ($date0) || is_null ($date1))
			return false;
		return
			$date0->year == $date1->year &&
			$date0->month == $date1->month &&
			$date0->day == $date1->day;
	}

	public static function lt ($date0, $date1) {
		if ($date0->year != $date1->year) return $date0->year < $date1->year;
		if ($date0->month != $date1->month) return $date0->month < $date1->month;
		if ($date0->day != $date1->day) return $date0->day < $date1->day;
		return false;
	}

	public static function le ($date0, $date1) {
		if ($date0->year != $date1->year) return $date0->year < $date1->year;
		if ($date0->month != $date1->month) return $date0->month < $date1->month;
		if ($date0->day != $date1->day) return $date0->day < $date1->day;
		return true;
	}

	public static function gt ($date0, $date1) {
		if ($date0->year != $date1->year) return $date0->year > $date1->year;
		if ($date0->month != $date1->month) return $date0->month > $date1->month;
		if ($date0->day != $date1->day) return $date0->day > $date1->day;
		return false;
	}

	public static function ge ($date0, $date1) {
		if ($date0->year != $date1->year) return $date0->year > $date1->year;
		if ($date0->month != $date1->month) return $date0->month > $date1->month;
		if ($date0->day != $date1->day) return $date0->day > $date1->day;
		return true;
	}

	public static function today () {
		list ($y, $m, $d) = explode ('-', date ('Y-m-d'));
		return new ess_Date ($y, $m, $d);
	}

	public static function from_str ($str) {
		$matches = null;

		if (preg_match ('/^ \\s* $/x', $str))
			return null;

		if (preg_match ('/^ \\s* (\\d{4}) - (\\d{1,2}) - (\\d{1,2}) \\s* $/x', $str, $matches))
			return new ess_Date ($matches [1], $matches [2], $matches [3]);

		if (preg_match ('/^ \\s* (\\d{1,2}) [	\\\\\\/-] (\\d{1,2}) [\\\\\\/-] (\\d{1,2}) \\s* $/x', $str, $matches))
			return new ess_Date ($matches [3] + 2000, $matches [2], $matches [1]);

		if (preg_match ('/^ \\s* (\\d{1,2}) \\/ (\\d{1,2}) \\/ (\\d{4}) \\s* $/x', $str, $matches))
			return new ess_Date ($matches [3], $matches [2], $matches [1]);

		throw new Exception ('Invalid date format');
	}
	
	public function toukdate() {
		return sprintf ('%02d/%02d/%04d', $this->day, $this->month, $this->year);
	}
}

?>
