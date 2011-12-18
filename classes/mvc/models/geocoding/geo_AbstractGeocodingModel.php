<?php
abstract class geo_AbstractGeocodingModel {
	
	const STATUS_SUCCESS = 'OK';
	const STATUS_DENIED = 'REQUEST_DENIED';
	const STATUS_LIMIT = 'OVER_QUERY_LIMIT';
	
	
	protected $baseUrl = 'http://maps.google.com/maps/api/geocode/';
	#protected $apiKey = 'ABQIAAAAezQae18676hVM8lI76hjfhTBS09fDvARAlVcmZ7bIABr21-gxxTkv-hEL5na4iehoxh20n6WIqVl_g' // registered to chris@mou.me.uk
	protected $outputType = 'json';
	protected $parameters = array(); // Parameters differ depending on whether you're geocoding or reverse geocoding
	
	public $response = false; // Entire response
	protected $status = false;
	
	
	abstract protected function validateParameters(); // Validate parameters are correct, pre-submission
	abstract public function getResult(); // Method to grab the part of the response we need - ie, postcode if reverse geocoding  
	
	protected function validateResponse() {
		$a = json_decode($this->response);
		$this->status = $a->status; 
	}
	
	public function getResponse() {
		return $this->response; // Just in case you want the entire response
	}
	
	protected function buildBaseUrl() {
		return sf('%s%s', $this->baseUrl, $this->outputType);
	}
	
	protected function buildParameters() {
		$ret = "";
		foreach ($this->parameters as $key=>$value) {
			$ret .= sf('%s=%s&', $key, $value);
		}
		return $ret;
	}
	
	protected function getRequestUrl() {
		return sf('%s?%s', $this->buildBaseUrl(), $this->buildParameters());
		
	}
	
	public function setParameter($key, $value) {
		$this->parameters[$key] = rawurlencode($value);
	}
	
	public function doGeocode() {
		$this->validateParameters();
		$this->doRequest();
		$this->validateResponse();
	}
	
	private function doRequest() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->getRequestUrl());
		// what to post
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$this->response = $result;
	}
}
?>