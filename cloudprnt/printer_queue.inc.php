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
		if ($file != "")
		{
			// Remove file extension
			$filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
			$print_order_data = file_get_contents($queuepath.$filename);
			$exploded = explode('_', $print_order_data);
			$current_copy = $exploded[0];
			$order_id = $exploded[2];
			$last_copy = star_cloudprnt_queue_get_last_copy_count($order_id);
			// Duplicate detected
			
			/*
			if ($current_copy <= $last_copy)
			{
				error_log("BAM!!!!!");
				// Delete duplicate print job
				unlink($queuepath.$file);
				unlink($queuepath.$filename);
				// Call method again to check next file
				$file = star_cloudprnt_queue_get_next_job($printerMac);
			}
			*/
			
		}
		return $file;
	}
	
	// Adds a file to the next available queue position
	function star_cloudprnt_queue_add_print_job($printerMac, $filepath, $copycount)
	{	
		for ($i = 0; $i < $copycount; $i++)
		{
			$filename = star_cloudprnt_queue_get_next_position($printerMac);
			$extension = pathinfo($filepath, PATHINFO_EXTENSION);
			$printerFolder = star_cloudprnt_get_printer_folder($printerMac);
			copy($filepath, star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename.'.'.$extension));
			$fh = fopen(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename, "w");
			fwrite($fh, $i.'_'.basename($filepath));
			fclose($fh);
		}
	}
	
	// Removes the file that was in the front of the queue (i.e. after it has been printed)
	function star_cloudprnt_queue_remove_last_print_job($printerMac)
	{
		// Get last print job details
		$filename = star_cloudprnt_queue_get_next_job($printerMac);
		$fileparts = explode('.', $filename);
		$filename2 = $fileparts[0];
		$printerFolder = star_cloudprnt_get_printer_folder($printerMac);
		$filepath = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename);
		$filepath2 = star_cloudprnt_get_os_path(STAR_CLOUDPRNT_PRINTER_DATA_SAVE_PATH.'/'.$printerFolder.'/queue/'.$filename2);
				
		// Save successful printed job in order history
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		if (file_exists($history_path))
		{
			$fh = fopen($history_path, 'a');
			fwrite($fh, preg_replace('/\\.[^.\\s]{3,4}$/', '', file_get_contents($filepath2)).'_'.time().PHP_EOL);
			fclose($fh);
		}
		
		// Delete the print job
		if ($filename != "" && file_exists($filepath))
		{
			unlink($filepath);
			if (file_exists($filepath2)) unlink($filepath2);
		}
	}
	
	function star_cloudprnt_queue_get_order_history()
	{
		$history = array();
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		$fh = fopen($history_path, "r");
		if ($fh)
		{
			while (($line = fgets($fh)) !== false)
			{
				$history[] = $line;
			}
			fclose($fh);
		}
		return $history;
	}
	
	function star_cloudprnt_queue_get_last_copy_count($order_id)
	{
		$history = array();
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		$fh = fopen($history_path, "r");
		$count = -1;
		if ($fh)
		{
			while (($line = fgets($fh)) !== false)
			{
				$exploded = explode('_', $line);
				$copy = intval($exploded[0]);
				$oid = intval($exploded[2]);
				if (($oid == $order_id) && ($copy > $count)) $count = $copy;
			}
			fclose($fh);
		}
		return $count;
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
		if (!empty($queueItems))
		{
			ksort($queueItems);
			return $queueItems;
		}
		return null;
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