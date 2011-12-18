<?php
class geo_GeocodingModel extends geo_AbstractGeocodingModel {
	
	protected $parameters = array(
		'address'	=> '',
		'sensor'	=> 'false'
	);
	
	protected function validateParameters() {
		// TODO
	}
	
	public function getResult() {
		if ($this->status != self::STATUS_SUCCESS) return false;
		else {
			$a = json_decode($this->response);
			return $a->results[0]->geometry->location;
		}
	}
	
	static public function go($parameters=array()) {
		$a = new self;
		foreach ($parameters as $key => $val) {
			$a->setParameter($key, $val);
		}
		$a->doGeocode();
		return $a->getResult();
	}
}
?>