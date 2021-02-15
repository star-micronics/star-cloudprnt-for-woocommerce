<?php
	include_once('cloudprnt_conf.inc.php');
	
	// Generate the correct printer dcommand driver class for the specified job format
	function star_cloudprnt_command_generator(&$selectedPrinter, &$file)
	{
		$printer = NULL;
		$format = $selectedPrinter['format'];
		
		if ($format == "txt") {	
			$printer = new Star_CloudPRNT_Text_Plain_Job($selectedPrinter, $file);					// Plain text output only
		} else if ($format == "slt") {
			$printer = new Star_CloudPRNT_Star_Line_Mode_Job($selectedPrinter, $file);			// Star Line Mode for Thermal Printers
		} else if ($format == "slm") {
			$printer = new Star_CloudPRNT_Star_Line_Mode_Job($selectedPrinter, $file);			// Star Line Mode for Dot-Matrix printers
		} else if ($format == "spt") {
			$printer = new Star_CloudPRNT_Star_Prnt_Job($selectedPrinter, $file);						// StarPRNT
			
		} else {
			$printer = new Star_CloudPRNT_Text_Plain_Job($selectedPrinter, $file);					// Fall back to plain text
		}

		return $printer;
	}	

	// Returns file friendly name for the printer mac address
	function star_cloudprnt_get_printer_folder($printerMac)
	{
		return str_replace(":", ".", $printerMac);
	}
	
	// Converts file friednly printer mac address to proper printer mac address
	function star_cloudprnt_get_printer_mac($printerFolder)
	{
		return str_replace(".", ":", $printerFolder);
	}
	
	// Returns a list of all printers and their data, that are currently or has in the past polled the server
	function star_cloudprnt_get_printer_list()
	{
		$list = array();
		foreach (glob(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/*", GLOB_ONLYDIR) as $printerpath)
		{
			$printerMac = star_cloudprnt_get_printer_mac(basename($printerpath));
			
			$jsonpath = $printerpath."/communication.json";
			$printerdata = json_decode(file_get_contents($jsonpath), true);
			$printerdata['lastActive'] = filemtime($jsonpath);
			
			$jsonpath = $printerpath."/data.json";
			$printerdata2 = json_decode(file_get_contents($jsonpath), true);
			$printerdata['name'] = $printerdata2['name'];
			$printerdata['ipAddress'] = $printerdata2['ipAddress'];
			
			$jsonpath = $printerpath."/additional_communication.json";
			$printerdata3 = json_decode(file_get_contents($jsonpath), true);
			foreach ($printerdata3['clientAction'] as $data)
			{
				$printerdata[$data['request']] = $data['result'];
			}
			
			$printerdata['printerOnline'] = star_cloudprnt_is_printer_online($printerdata['GetPollInterval'], filemtime($printerpath."/communication.json"));
			
			$list[$printerMac] = $printerdata;
		}
		return $list;
	}
	
	// Returns printer connectivity status, note this does not factor in real printer status (such as offline due to cover open)
	function star_cloudprnt_is_printer_online($pollRate, $lastCommunicationTime)
	{
		if ((time()-$lastCommunicationTime) > ($pollRate+5)) return false;
		return true;
	}
	
	class Star_CloudPRNT_Printer
	{
		private $printer_mac;
		
		public function __construct($printer_mac)
		{
			$this->printer_mac = $printer_mac;
		}
		
		// Returns all the data of a specific printer that is or has in the past polled the server
		function getPrinterData()
		{
			$printerpath = STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($this->printer_mac);

			$printerdata = array();
			$jsonpath = $printerpath."/communication.json";
			if (file_exists($jsonpath)) $printerdata = json_decode(file_get_contents($jsonpath), true);
			
			$jsonpath = $printerpath."/data.json";
			if (file_exists($jsonpath))
			{
				$printerdata2 = json_decode(file_get_contents($jsonpath), true);
				$printerdata['name'] = $printerdata2['name'];
				$printerdata['ipAddress'] = $printerdata2['ipAddress'];
				$printerdata['lastActive'] = filemtime($jsonpath);
			}
			
			$jsonpath = $printerpath."/additional_communication.json";
			if (file_exists($jsonpath))
			{
				$printerdata3 = json_decode(file_get_contents($jsonpath), true);
				foreach ($printerdata3['clientAction'] as $data)
				{
					$printerdata[$data['request']] = $data['result'];
				}
				$printerdata['printerOnline'] = star_cloudprnt_is_printer_online($printerdata['GetPollInterval'], filemtime($printerpath."/data.json"));
			}

			return $printerdata;
		}
		
		// Creates printer data file for a newly joined printer
		function createPrinterData($ip)
		{
			$jsonpath = STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($this->printer_mac)."/data.json";
			
			if (!file_exists($jsonpath))
			{
				$printerdata = array("name" => $this->printer_mac,
								"macAddress" => $this->printer_mac,
								"ipAddress" => $ip,
								"isOnline" => true);
				$fp = fopen($jsonpath, 'w');
				fwrite($fp, json_encode($printerdata));
				fclose($fp);
			}
			else $this->updatePrinterData("ipAddress", $ip);

			return true;
		}
		
		// Updates existing data for existing printers that are polling the server
		function updatePrinterData($variable, $value)
		{
			$jsonpath = STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($this->printer_mac)."/data.json";
			if (file_exists($jsonpath))
			{
				$printerdata = json_decode(file_get_contents($jsonpath), true);
				$printerdata[$variable] = $value;
				$fp = fopen($jsonpath, 'w');
				fwrite($fp, json_encode($printerdata));
				fclose($fp);
			}
		}
		
		// Generic function to delete a directory, regardless if it is empty or not
		function deleteDirectory($dir)
		{
			if (!file_exists($dir)) return true;

			if (!is_dir($dir)) return unlink($dir);

			foreach (scandir($dir) as $item) 
			{
				if ($item == '.' || $item == '..') continue;

				if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
			}
			return rmdir($dir);
		}
		
		// Used to delete all data on printers that have previously polled the server
		function deletePrinter()
		{
			$printerdata = $this->getPrinterData($this->printer_mac);
			if (!$printerdata['printerOnline'])
			{
				$dirpath = STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($this->printer_mac);
				$this->deleteDirectory($dirpath);
			}
		}
	}
?>