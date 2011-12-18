<?php

class memcache_Wrapper {

	private $memcache;
	private $adaptee;

	public function __construct ($adapteeObject) {
		$this->adaptee = $adapteeObject;
	}

	public function mc_connect($host, $port) {
		$this->memcache = new Memcache;
		$this->memcache->connect($host, $port) or trigger_error("Could not connect to mem cache");
	}

	public function mc_flush() {
		$this->memcache->flush();
	}

	public function mc_select_1_i ($memCacheData, $query_etc) {
		// use quotef to evaluate the format string
		$args = func_get_args ();
		$memCacheData = $args[0];
		$query = $this->quotef_array (array_slice($args,1));

		// perform the query
		$row = $this->mc_select_1 ($memCacheData, '%l', $query);

		if (! $row) return null;
		$keys = $row->keys ();
		return $row->i ($keys [0]);
	}

	public function mc_select_1 ($memCacheData, $query_etc) {

		$args = func_get_args ();
		$rows = call_user_func_array (array ($this, 'mc_select'), $args);

		if (count ($rows) == 1)
			return $rows [0];
		if (count ($rows) == 0)
			return null;
		throw new sql_Exception ('Multiple rows in select_1()');


	}

	public function mc_select ($memCacheData, $query_etc) {
		$args = func_get_args ();
		$memCacheData = $args[0];

		// is the data an array of settings or just expirey time
		if (is_array($memCacheData)) {
			$expires 	= $memCacheData['expires'];
			$force 		= $memCacheData['force'];
		} elseif (is_int($memCacheData)) {
			$expires = $memCacheData;
			$force = false;
		}

		$query = $this->quotef_array (array_slice($args,1));
		$queryRef = md5($query);

		// if we're not forcing a refresh then look in cache
		if (!$force) {
			$cache =  $this->memcache->get($queryRef);
			if (is_array($cache)) return $cache['result'];
		}
		// lets get the result and cache it...
		$result =  call_user_func (array ($this->adaptee, "select_real"),$query);
		//cache
		$cache =  @$this->memcache->set($queryRef,array("result"=>$result), MEMCACHE_COMPRESSED, $expires);
		
		return $result;

	}
	// just calls adaptee
	public function __get ($name) {
		return call_user_func (array ($this->adaptee, $name));
	}
	public function __call ($name,$args) {
		return call_user_func_array (array ($this->adaptee, $name),$args);
	}
	public function mc_test() {

			$cache =  $this->memcache->get("test");
			if ($cache) {
				return $cache;
			} else {
				$time =  date("H:i:s");
				$this->memcache->set("test", $time, 0, 60);
				return $time;
			}
	}

	public function getMemcache ()
	{
		return $this->memcache;
	}
}

?>