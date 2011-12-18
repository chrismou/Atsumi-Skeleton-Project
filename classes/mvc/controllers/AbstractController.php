<?php
class AbstractController extends mvc_AbstractController {
	
	function preProcess() {
		parent::preProcess();
		
		Atsumi::error__setErrorReporting(E_ALL);
        Atsumi::error__setDisplayErrors(true);		
		
		$this->set('siteName', $this->app->get_siteName);
	}
	
}
?>
