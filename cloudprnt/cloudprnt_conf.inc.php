<?php
	// Store paper widths in pixels
	define('STAR_CLOUDPRNT_PAPER_SIZE_THREE_INCH', 576);
	define('STAR_CLOUDPRNT_PAPER_SIZE_FOUR_INCH', 832);
	define('STAR_CLOUDPRNT_PAPER_SIZE_ESCPOS_THREE_INCH', 512);
    define('STAR_CLOUDPRNT_PAPER_SIZE_DOT_THREE_INCH', 210);

	// Store paper widths in pixels
	define('STAR_CLOUDPRNT_MAX_CHARACTERS_THREE_INCH', 48);
	define('STAR_CLOUDPRNT_MAX_CHARACTERS_TWO_INCH', 32);
	define('STAR_CLOUDPRNT_MAX_CHARACTERS_DOT_THREE_INCH', 42);
	define('STAR_CLOUDPRNT_MAX_CHARACTERS_FOUR_INCH', 69);
	
	// Used to determine what operating system the server is running
	define("STAR_CLOUDPRNT_WINDOWS", 0);
	define("STAR_CLOUDPRNT_UNIX", 1);
	define('STAR_CLOUDPRNT_WINDOWS_PATH_SEPERATOR', '\\');
	define('STAR_CLOUDPRNT_UNIX_PATH_SEPERATOR', '/');
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') define("STAR_CLOUDPRNT_OPERATING_SYSTEM", STAR_CLOUDPRNT_WINDOWS);
	else define("STAR_CLOUDPRNT_OPERATING_SYSTEM", STAR_CLOUDPRNT_UNIX);

	// Used to convert URL path seperators to the correct syntax depending on the OS being used
	function star_cloudprnt_get_os_path($path)
	{
		switch (STAR_CLOUDPRNT_OPERATING_SYSTEM)
		{
			case STAR_CLOUDPRNT_WINDOWS:
				return str_replace(STAR_CLOUDPRNT_UNIX_PATH_SEPERATOR, STAR_CLOUDPRNT_WINDOWS_PATH_SEPERATOR, $path);
			case STAR_CLOUDPRNT_UNIX:
				return str_replace(STAR_CLOUDPRNT_WINDOWS_PATH_SEPERATOR, STAR_CLOUDPRNT_UNIX_PATH_SEPERATOR, $path);
			default:
				return str_replace(STAR_CLOUDPRNT_WINDOWS_PATH_SEPERATOR, STAR_CLOUDPRNT_UNIX_PATH_SEPERATOR, $path);
		}
	}
	
	function star_cloudprnt_get_previous_dir_path($path, $amount)
	{
		$dir = $path;
		$dir_length = strlen($dir)-1;
		if ($dir[$dir_length] === '/') $dir = substr($dir, 0, $dir_length);
		else if ($dir[$dir_length] === '\\') $dir = substr($dir, 0, $dir_length);
		for ($i = 0; $i < $amount; $i++)
		{
			$pos = strrpos($dir, '/');
			if ($pos === false) $pos = strrpos($dir, '\\');
			if ($pos === false) break;
			else $dir = substr($dir, 0, $pos);
		}
		return $dir;
	}
	
	function star_cloudprnt_get_plugin_root()
	{
		$plugin_dir = '';
		if (defined( 'ABSPATH' ))
		{
			$plugin_dir = plugin_dir_path(__FILE__);
			if (basename($plugin_dir) === 'cloudprnt') $plugin_dir = star_cloudprnt_get_previous_dir_path($plugin_dir, 3);
			else $plugin_dir = star_cloudprnt_get_previous_dir_path($plugin_dir, 2);
		}
		else 
		{
			$plugin_dir = getcwd();
			$plugin_dir = star_cloudprnt_get_previous_dir_path($plugin_dir, 3);
		}
		return $plugin_dir;
	}
	
	// STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH = Sets a working directory where all printer data can be stored
	define('STAR_CLOUDPRNT_DATA_FOLDER_PATH', star_cloudprnt_get_plugin_root().star_cloudprnt_get_os_path('/star-cloudprnt'));
	define('STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH', STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path('/printerdata'));
	define('STAR_CLOUDPRNT_PRINTER_PENDING_SAVE_PATH', STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path('/pending'));
	// STAR_CLOUDPRNT_ADDITIONAL_DATA_INTERVAL = Adjust how often (in seconds) the server requests printer configuration data (e.g. poll interval)
	define('STAR_CLOUDPRNT_ADDITIONAL_DATA_INTERVAL', 120);
	
	define('STAR_CLOUDPRNT_SPOOL_FILE_FORMAT', 'txt');
?>