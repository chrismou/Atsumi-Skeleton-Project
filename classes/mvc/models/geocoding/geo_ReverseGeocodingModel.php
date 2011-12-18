<?php
class geo_ReverseGeocodingModel extends geo_AbstractGeocodingModel {
	
	const STATUS_MALFORMED_LATLNG = 'MALFORMED LATITUDE/LONGITUDE COORDINATES';
	
	protected $parameters = array(
		'latlng'	=> '',
		'sensor'	=> 'true'
	);
	
	protected function validateParameters() {
		if (!preg_match("/^[+-]?\d+\.\d+, ?[+-]?\d+\.\d+$/", $_GET['latlng']))
			Throw new Exception(self::STATUS_MALFORMED_LATLNG);
	}
	
	public function getResult() {
		if ($this->status != self::STATUS_SUCCESS) return false;
		else {
			$a = json_decode($this->response);
			return $a->results[1]->address_components[0]->long_name;
		}
	}
	
}
?>