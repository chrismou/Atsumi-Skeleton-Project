<?php

function sf_num_percents ($s) {
	if (! is_string ($s)) throw new Exception ();
	$pos = 0;
	$count = 0;
	while (true) {
		$pos = strpos ($s, '%', $pos);
		if ($pos === false) return $count;
		if ($pos + 2 > strlen ($s))
			throw new Exception ('Invalid format string');
		if (substr ($s, $pos + 1, 1) != "%")
			$count++;
		$pos += 2;
	}
}

function serverHostName() {
	$name = '';
	try {
		if (function_exists('getHostName')) {
			$name = getHostName();
		} else {
			$a = fopen('/etc/hostname', 'r');
			$name = fread($a, 20);
			fclose($a);
		}
	} catch (Exception $e) { }
	return trim($name);
}

?>
