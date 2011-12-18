<?php
class HomeController extends AbstractController {

	function preProcess() {
		parent::preProcess();
	}

	function page_index() {		
		$this->setView('HomeView');
		$this->set('siteName', $this->app->get_siteName);
	}
	
}
?>
