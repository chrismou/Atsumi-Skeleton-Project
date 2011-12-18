<?php

class MouSettings extends ProductionSettings {

	protected function createSettings() {
		
		parent::createSettings();
		
		$this->setArray(array(
				'mainDomain' 			=> 'mougeotwit.dev',
				'debug'					=> true,
				'logPath'				=> '/var/log/atsumi/',
				'memcache'				=> true,
				'mcHost'				=> 'localhost',
				'mcPort'				=> 11211,
				/* Emails */
				'emailFrom'				=> 'auto@localhost',
				'emailSender'			=> 'auto@localhost',
				'email_host'			=> 'localhost',
				'email_protocol'		=> 'smtp'
			)
		);
	}
}
?>
