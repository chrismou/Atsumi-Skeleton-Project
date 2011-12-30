<?php
class AbstractController extends mvc_AbstractController {
	
	function preProcess() {
		parent::preProcess();
		
		Atsumi::error__setErrorReporting(E_ALL);
        Atsumi::error__setDisplayErrors($this->app->get_debug);		
		
        /* global view data */
		$this->set('siteName', $this->app->get_siteName);
	}
	
}
?>
