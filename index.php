<?php
// Get Atsumi
require_once("../atsumi/init.php");
putenv("TZ=Europe/London");

// Add class areas to the class loader
atsumi_Loader::references(array(
	'Atsumi-Skeleton-Project'	=> 'app mvc',
	'atsumi'					=> 'mvc widgets validators session cache'
));


// Pick Settings
switch($_SERVER['SERVER_ADMIN']) {
	case "chris@localhost" :
		$settings = new MouSettings();
		break;
	default:
		$settings = new ProductionSettings();
		break;
}

// Initalise sencha and the url parser
Atsumi::initApp($settings);
Atsumi::app__setUriParser('uriparser_Gyokuro');
// Execute Atsumi
try {
	Atsumi::app__go($_SERVER['REQUEST_URI']);
} catch(app_PageNotFoundException $e) {
	Atsumi::app__go("/error/404/");
}
// Render the processed output
Atsumi::app__render();
?>
