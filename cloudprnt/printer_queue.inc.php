<?php
	include_once('printer.inc.php');
	
	// Returns a integer of the next available queue position
	function star_cloudprnt_queue_get_next_position($printerMac)
	{
		$heighestQueueNumber = 0;
		$queuepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/");
		if ($handle = opendir($queuepath)) 
		{
			while (false !== ($entry = readdir($handle))) 
			{
				if ($entry != "." && $entry != "..") 
				{
					// Remove file extension
					$filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $entry);
					if (is_numeric($filename))
					{
						$queueNumber = intval($filename);
						if ($heighestQueueNumber < $queueNumber) $heighestQueueNumber = $queueNumber;
					}
				}
			}
			closedir($handle);
		}
		return ++$heighestQueueNumber;
	}
	
	// Returns file name of the next job to print in the queue or empty string if there are no jobs in the queue
	function star_cloudprnt_queue_get_next_job($printerMac)
	{
		$file = "";
		$lowestQueueNumber = -1;
		$queuepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/");
		if ($handle = opendir($queuepath)) 
		{
			$firstLoop = true;
			while (false !== ($entry = readdir($handle))) 
			{
				if ($entry != "." && $entry != "..") 
				{
					// Remove file extension
					$filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $entry);
					if (is_numeric($filename))
					{
						$queueNumber = intval($filename);
						if ($firstLoop && strpos($entry, ".") !== false)
						{
							$firstLoop = false;
							$file = $entry;
							$lowestQueueNumber = $queueNumber;
						}
						else if ($lowestQueueNumber > $queueNumber && strpos($entry, ".") !== false)
						{
							$file = $entry;
							$lowestQueueNumber = $queueNumber;
						}
					}
				}
			}
			closedir($handle);
		}
		return $file;
	}
	
	// Adds a file to the next available queue position
	function star_cloudprnt_queue_add_print_job($printerMac, $filepath)
	{
		$filename = star_cloudprnt_queue_get_next_position($printerMac);
		$extension = pathinfo($filepath, PATHINFO_EXTENSION);
		$printerFolder = star_cloudprnt_get_printer_folder($printerMac);
		copy($filepath, star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename.'.'.$extension));
		$fh = fopen(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename, "w");
		fwrite($fh, basename($filepath));
		fclose($fh);
	}
	
	// Removes the file that was in the front of the queue (i.e. after it has been printed)
	function star_cloudprnt_queue_remove_last_print_job($printerMac)
	{
		$filename = star_cloudprnt_queue_get_next_job($printerMac);
		$fileparts = explode('.', $filename);
		$filename2 = $fileparts[0];
		$printerFolder = star_cloudprnt_get_printer_folder($printerMac);
		
		$filepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename);
		$filepath2 = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename2);
		if ($filename != "" && file_exists($filepath))
		{
			unlink($filepath);
			if (file_exists($filepath2)) unlink($filepath2);
		}
	}
	
	// Returns a list of queue items in priority order
	function star_cloudprnt_queue_get_queue_list($printerMac)
	{
		$queueItems;
		$queuepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/");
		if ($handle = opendir($queuepath)) 
		{
			while (false !== ($entry = readdir($handle))) 
			{
				if ($entry != "." && $entry != "..") 
				{
					// Remove file extension
					$filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $entry);
					if (is_numeric($filename) && strpos($entry, '.') === false)
					{
						$filepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/".$filename);
						$queueItems[$filename] = preg_replace('/\\.[^.\\s]{3,4}$/', '', file_get_contents($filepath));
					}
				}
			}
			closedir($handle);
		}
		if (!empty($queueItems)) ksort($queueItems);
		return $queueItems;
	}
	
	// Delete all items in the printer queue
	function star_cloudprnt_queue_clear_list($printerMac)
	{
		$queuepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/");
		if ($handle = opendir($queuepath)) 
		{
			while (false !== ($entry = readdir($handle))) 
			{
				if ($entry != "." && $entry != "..") 
				{
					$filepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH."/".star_cloudprnt_get_printer_folder($printerMac)."/queue/".$entry);
					unlink($filepath);
				}
			}
			closedir($handle);
		}
	}
?>