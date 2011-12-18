<?php

class mgt_TwitterTweetModel {
	
	public $id;
	public $created;
	public $source;
	public $tweet;
	public $user;
	public $userId;
	public $userImg;
	public $geo;
	public $location;
	public $toUser;
	public $toUserId;
	
	public function __construct($id, $created, $source, $tweet, $user, $userId, $userImg, $geo, $location, $toUser = null, $toUserId = null) {
		try {
			$this->id = $id;
			$this->created = $created;
			$this->source = $source;
			$this->tweet = $tweet;
			$this->user = $user;
			$this->userId = $userId;
			$this->userImg = $userImg;
			$this->geo = $geo;
			$this->location = $location;
			$this->toUser = $toUser;
			$this->toUserId = $toUserId;
		} catch (mgt_TweetIncompleteInfoException $e) {
			throw $e;
		}
	}
	
	public function fixText($in) {
		$in = $this->addUrlLinks($in);
		$in = $this->addHashTagLinks($in);
		$in = $this->addAtUserLinks($in);
		return $in;
	}
	
	private function addUrlLinks($in) {
		$out = preg_replace("#((http|https)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie","'<a href=\"$1\" target=\"_blank\" rel=\"nofollow\">$3</a>$4'", $in); 
		return $out;
	}
	
	private function addHashTagLinks($in) {
		$out = preg_replace('/(^|\s)#(\w+)/', '\1<a href="http://search.twitter.com/search?q=%23\2">#\2</a>', $in);
		return $out;
	}
	
	private function addAtUserLinks($in) {
		$out = preg_replace('/(^|\s)@(\w+)/', '\1<a href="http://www.twitter.com/\2">@\2</a>',$in);;
		return $out;
	}
	
	public function fixDate($in) {
		$a = "";
		$pubdate = strtotime($in);
		$now = time();
		$difference = $now - $pubdate;
		
		$days = floor($difference / 84600);
		$difference -= 84600 * floor($difference / 84600);
		
		$hours = floor($difference / 3600);
		$difference -= 3600 * floor($difference / 3600);
		
		$minutes = floor($difference / 60);
		$seconds = $difference - (60 * floor($difference / 60));
		
		if ( $days > 0 ) {
			$a .= $days . " day";
			if ( $days > 1 ) {
				$a .= "s";
			}
			$a .= " ago";
			return $a;
		}
		if ( $hours > 0 ) {
			$a .= $hours . " hour";
			if ( $hours > 1 ) {
				$a .= "s";
			}			
			if ($hours > 2) {
				$a .= " ago";
				return $a;
			} else {
				$a .= ", ";
			}
		}
		if ( $minutes > 0 ) {
			$a .= $minutes . " minute";
			if ( $minutes > 1 ) {
				$a .= "s";
			}
			if ( $minutes > 2 ) {
				$a .= " ago";
				return $a;
			} else {
				$a .= ", ";
			}
		}
		$a .= $seconds . " seconds ago";	
		return $a;
	}

}
?>
