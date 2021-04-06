<?php
	// Function to recursively delete a directory and it's sub directories
	function rrmdir($dir)
	{
		if (is_dir($dir))
		{ 
			$objects = scandir($dir); 
			foreach ($objects as $object)
			{ 
				if ($object != "." && $object != "..")
				{ 
					if (is_dir($dir."/".$object)) rrmdir($dir."/".$object);
					else unlink($dir."/".$object); 
				} 
			}
			rmdir($dir); 
		} 
	}
	// Get file path config
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') include_once(plugin_dir_path(__FILE__).'cloudprnt\\cloudprnt_conf.inc.php');
	else include_once(plugin_dir_path(__FILE__).'cloudprnt/cloudprnt_conf.inc.php');
	// Delete CloudPRNT storage folders
	if (is_dir(STAR_CLOUDPRNT_DATA_FOLDER_PATH)) rrmdir(STAR_CLOUDPRNT_DATA_FOLDER_PATH);
	// Remove all saved options, all other data is part of flat file db that is removed when the plugin is deleted
	delete_option('star-cloudprnt-select');
	delete_option('star-cloudprnt-printer-select');
	delete_option('star-cloudprnt-print-logo-top-cb');
	delete_option('star-cloudprnt-print-logo-top-input');
	delete_option('star-cloudprnt-print-logo-bottom-cb');
	delete_option('star-cloudprnt-print-logo-bottom-input');

	delete_option("star-cloudprnt-printer-encoding-select");
	delete_option("star-cloudprnt-print-copies-input");

	delete_option("star-cloudprnt-trigger");

	delete_option("star-cloudprnt-print-header-title");

	delete_option("star-cloudprnt-print-items-print-id");
	delete_option("star-cloudprnt-print-items-print-sku");
	delete_option("star-cloudprnt-print-items-footer-message");

	delete_option("star_cloudprnt_setup_section");
	delete_option("star-cloudprnt-print-order-meta-reformat-keys");
	delete_option("star-cloudprnt-print-order-meta-hidden");
	delete_option("star-cloudprnt-print-order-meta-exclusions");

	delete_option("star-cloudprnt-buzzer-start");
	delete_option( "star-cloudprnt-buzzer-end");


?>