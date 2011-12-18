<?php

class ProductionSettings extends atsumi_AbstractAppSettings {

	protected function createSettings() {
		$this->setArray(array(
				/* Current Version */
				'version'				=> '0.1a',
				/* Branding, Tags, keywords, Slogans */
				'siteName'				=> 'Skeleton',
				'mainDomain' 			=> 'skeleton.dev',
				/* System Settings */
				'debug'					=> false,
				'logPath'				=> '/var/log/atsumi/',
				/* Database */
				'dbName'				=> '',
				'dbPort'				=> '5432',
				'dbUser'				=> '',
				'dbPassword'			=> '',
				'dbHost'				=> 'localhost',
				/* Caching */
				'memcache'				=> true,
				'mcHost'				=> 'localhost',	
				'mcPort'				=> 11211,
				/* Emails */
				'emailFrom'				=> 'auto@mou.me',
				'emailSender'			=> 'auto@mou.me',
				'email_host'			=> 'localhost',
				'email_protocol'		=> 'smtp'
			)
		);
	}

	/* Current domain passed in so don't depend on apache 2 env vars */

	public function __construct() {
		$this->createSettings();
		
		#die($_SERVER['SERVER_ADDR']);
		
		// Don't initialise session if request requires file controller
		if (substr($_SERVER['REQUEST_URI'],0, 6) != '/file/') {
			//$this->init_session();
		}
		
		$this->configureErrorHandler();
		
	}
	
	/* Configure the error handlers */
	public function configureErrorHandler() {
		Atsumi::error__addObserver(new listener_LogToFile($this->get_logPath), atsumi_ErrorHandler::EVENT_EXCEPTION_FC);
		Atsumi::error__setRecoverer(new recoverer_DisplayAndExit());
	}

	/* GoneTooSoon Site Specification */
	public function init_specification() {
		return array(
			''	=> 'HomeController'
		);
	}

	/* Init Smarty */
	public function init_memcache() {
		return new cache_MemcacheHandler($this->get('mcHost'), $this->get('mcPort'));
	}

	/* Init Database */
	public function init_db() {
		try {
			$db = new memcache_Wrapper(new PostgreSqlDb());
			$db->mc_connect($this->get_mcHost,
							$this->get_mcPort
			);

			if (!$this->get_cache)
				$db->mc_flush();

			$db->connect(sf(
				'host=%s ', $this->get_dbHost,
				'port=%s ', $this->get_dbPort,
				'user=%s ', $this->get_dbUser,
				'password=%s ', $this->get_dbPassword,
				'dbname=%s ', $this->get_dbName
			));

			if ($this->get_debug) {
				$db->recordQueryTimes(true);
				atsumi_Debug::addDatabase($db);
			}
		} catch (Exception $e) {
			die("Failed to connect to database.");
		}
		return $db;
	}

	/* Init Session */
	public function init_session() {
		return session_Handler::getInstance();
	}

}
?>
