<?php

class mgt_TwitterGeoSearchModel {

	protected $baseUrl = "http://search.twitter.com/search.";
	protected $outputType = "json";
	protected $parameters = array(
		'q'				=> '',
		'geocode'		=> '',
		'lang'			=> 'en',
		'result_type'	=> 'recent'
	);

	public function __construct() {}
	
	static public function go($q, $lat="", $lng="", $miles="") {
		$a = new self;
		$a->setParameter("q", $q);
		$a->setParameter("geocode", sf('%s,%s,%smi', $lat, $lng, $miles));
		$a->doTwitterGeoSearch();
		return $a->getResults();
	}
	
	static public function goPaginated($url) {
		$a = new self;
		$requestUrl = sf('%s%s', $a->buildBaseUrl(), $url);
		$a->doRequest($requestUrl);
		return $a->getResults();
	}
	
	public function getResults() {
		$a = $this->loadFromResponse($this->response);
		return $a;
	}
	
	private function loadFromResponse($jsonIn) {
		$in = json_decode($jsonIn);
		
		$ret = array();
		$ret['next_page'] = (isset($in->next_page)) ? $in->next_page : false;
		$ret['previous_page'] = (isset($in->previous_page)) ? $in->previous_page : false;
		
		$tweets = array();
		foreach ($in->results as $tweet) {
			$a = new mgt_TwitterTweetModel(
				$tweet->id,
				$tweet->created_at,
				$tweet->source,
				$tweet->text,
				$tweet->from_user,
				$tweet->from_user_id,
				$tweet->profile_image_url,
				$tweet->geo,
				(isset($tweet->location) ? $tweet->location : null),
				(isset($tweet->to_user) ? $tweet->to_user : null),
				$tweet->to_user_id
			);
			$tweets[] = $a;
		}
		
		$ret['tweets'] = $tweets;
		return $ret;
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
	
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}
	
	protected function getRequestUrl() {
		return sf('%s?%s', $this->buildBaseUrl(), $this->buildParameters());
		
	}
	
	private function doTwitterGeoSearch() {
		$this->doRequest($this->getRequestUrl());
	}
	
	private function doRequest($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
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
