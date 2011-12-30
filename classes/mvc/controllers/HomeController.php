<?php
class HomeController extends AbstractController {

	function preProcess() {
		parent::preProcess();
	}

	function page_index() {		
		$this->setView('HomeView');
		
		$this->set('title', 'The home page');
		$this->set('htmlTitle', 'Home');
		$this->set('data', "content here");
	}
	
}
?>
