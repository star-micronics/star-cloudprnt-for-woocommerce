<?php
	function star_cloudprnt_get_selected_printer_mac($selectedPrinter)
	{
		$printerList = star_cloudprnt_get_printer_list();
		$printerMac;
		foreach ($printerList as $printer)
		{
			if ($printer['name'] == $selectedPrinter)
			{
				$printerMac = $printer['printerMAC'];
				break;
			}
		}
		return $printerMac;
	}
	
	function star_cloudprnt_change_printer_name()
	{
		$page = htmlspecialchars($_GET['page']);
		$selectedPrinter = htmlspecialchars(base64_decode($_GET['printersettings']));
		$settingsParam = base64_encode($selectedPrinter);
		$printerMac = star_cloudprnt_get_selected_printer_mac($selectedPrinter);

		if (isset($printerMac))
		{
			// Sanitize and escape before saving
			$newPrinterName = esc_attr(sanitize_text_field(base64_decode($_GET['npn'])));
			// Validate
			if (strlen($newPrinterName) < 3 || strlen($newPrinterName) > 35)
			{
				header('location: ?page='.$page.'&printersettings='.$settingsParam.'&errorCode=1');
				return;
			}
			// Save printer data in CloudPRNT
			$printer = new Star_CloudPRNT_Printer($printerMac);
			$printer->updatePrinterData("name", $newPrinterName);
			// Save selected printer in WordPress
			if ($selectedPrinter == get_option('printer-select')) update_option('printer-select', $newPrinterName);
			// Redirect to new printer page
			header('location: ?page='.$page.'&printersettings='.base64_encode($newPrinterName));
		}
	}
	
	function star_cloudprnt_clear_printer_queue()
	{	
		$page = htmlspecialchars($_GET['page']);
		$selectedPrinter = htmlspecialchars(base64_decode($_GET['printersettings']));
		$settingsParam = base64_encode($selectedPrinter);
		$printerMac = star_cloudprnt_get_selected_printer_mac($selectedPrinter);
		
		if (isset($printerMac))
		{
			star_cloudprnt_queue_clear_list($printerMac);
			header('location: ?page='.$page.'&printersettings='.$settingsParam);
		}
	}
	
	function star_cloudprnt_clear_order_history()
	{
		$page = htmlspecialchars($_GET['page']);
		$selectedPrinter = htmlspecialchars(base64_decode($_GET['printersettings']));
		$settingsParam = base64_encode($selectedPrinter);
		
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		$fh = fopen($history_path, 'w');
		fclose($fh);
		header('location: ?page='.$page.'&printersettings='.$settingsParam);
	}
	
	function star_cloudprnt_delete_printer()
	{

		$page = htmlspecialchars($_GET['page']);
		$selectedPrinter = htmlspecialchars(base64_decode($_GET['printersettings']));
		$printerMac = star_cloudprnt_get_selected_printer_mac($selectedPrinter);
		
		if (isset($printerMac))
		{
			$printer = new Star_CloudPRNT_Printer($printerMac);
			$printer->deletePrinter();
			header('location: ?page='.$page);
		}
	}

	function star_cloudprnt_render_delete_printer_section($page, $online, $settingsParam)
	{
		star_cloudprnt_render_settings_section_title("Delete Printer");
		
		if ($online){
			?>
				<span style="color: red">
					<span class="dashicons dashicons-no"></span>
					You cannot delete the printer whilst it is connected
				</span>
			<?php
		} else {
			?>
				<button class="button button-primary"
						onclick="location.href=\'?page=<?=$page?>&printersettings=<?=$settingsParam?>&dp\'">
						Delete Printer
				</button>
			<?php
		}
	}

	function star_cloudprnt_render_printer_info_section_script($page, $settingsParam)
	{
		?>
			<script>
				function showDiv() {
					if (document.getElementById('editPrinterNameContainer').style.display == "block")
					{
						document.getElementById('editPrinterNameContainer').style.display = "none";
						document.getElementById('changeNameLabel').innerHTML = "Rename";
					}
					else
					{
						document.getElementById('editPrinterNameContainer').style.display = "block";
						document.getElementById('changeNameLabel').innerHTML = "Hide";
					}
				}
				function savePrinterName()
				{
					var newName = htmlspecialchars(document.getElementById("printerName").value);
					window.location.href = '?page=<?=$page?>&printersettings=<?=$settingsParam?>&npn='+btoa(newName);
				}
			</script>
		
		<?php
	}

	function star_cloudprnt_render_printer_info_section($page, $settingsParam, $printerData)
	{
		star_cloudprnt_render_printer_info_section_script($page, $settingsParam);
		star_cloudprnt_render_settings_section_title("Printer Information");
		
		$onlineString = '<span style="color: orange">Unknown</span>';
		if ($printerData['printerOnline']) $onlineString = '<span style="color: green">Connected</span>';
		else $onlineString = '<span style="color: red">Not Connected</span>';
		
		$httpStatus = str_replace("%", " ", $printerData['statusCode']);
		$httpStatus = str_replace(" 20", " ", $httpStatus);
		
		?>
			<strong>Name:</strong> <?=$printerData['name']?> -
			<a id="changeNameLabel" href="javascript:void(0);" onclick='showDiv()'>Rename</a>
			<br>
			
			<div id="editPrinterNameContainer" style="display: none">
				<input id="printerName" type="text" name="printer" id="printer"
					placeholder="<?=$printerData['name']?>"
					value="<?=$printerData['name']?>"
					autocomplete="off">
				<a href="javascript: void(0);" onclick="savePrinterName()" style="padding-left: 10px">Save</a>
			</div>
			
			<?php
				if (isset($_GET['errorCode']) && $_GET['errorCode'] == 1)
				{
					echo '<script type="text/javascript">showDiv()</script><span style="color:red;">Error: The new printer name must be between 3 and 35 characters long.</span><br>';
				}
			?>
			
			<strong>Poll Interval:</strong> <?=$printerData['GetPollInterval']?><br>
			<strong>Connectivity:</strong> <?=$onlineString?><br>
			<strong>ASB Status Code:</strong> <?=$printerData['status']?><br>
			<strong>HTTP Status Code:</strong> <?$httpStatus?><br>
			<strong>Last Communication:</strong> <?=date("D j M y - H:i:s", $printerData['lastActive'])?>
		
		<?php
		
	}

	function star_cloudprnt_render_printer_identification_section($printerData)
	{
		star_cloudprnt_render_settings_section_title("Printer Identification");

		?>
			<strong>MAC Address:</strong> <?=strtoupper($printerData['printerMAC'])?><br>
			<strong>IP Address:</strong> <?=$printerData['ipAddress']?>
		<?php
	}
	
	function star_cloudprnt_render_printer_interface_section($printerData)
	{
		star_cloudprnt_render_settings_section_title("Interface");
		
		?>
			<strong>Client Type:</strong> <?=$printerData['ClientType']?><br>
			<strong>Client Version:</strong> <?=$printerData['ClientVersion']?>
		<?php
	}
	
	function star_cloudprnt_render_printer_encodings_section($printerData)
	{
		star_cloudprnt_render_settings_section_title("Supported Encodings");
		
		$encodings = explode(';', $printerData['Encodings']);
		foreach ($encodings as $encoding)
		{
			echo $encoding."<br>";
		}
	}
	
	function star_cloudprnt_render_printer_queue_section($page, $settingsParam, $printerData)
	{
		star_cloudprnt_render_settings_section_title("Print Queue");
		
		$queueItems = star_cloudprnt_queue_get_queue_list($printerData['printerMAC']);
		
		if (empty($queueItems))
		{
			echo 'No items found in printer queue.<br>';
		} else {
			?>
				<table>
					<thead>
						<tr>
							<th style="padding: 5px">Priority</th>
							<th>Order ID</th>
							<th>Queued On</th>
						</tr>
					</thead>
					<tbody>
						<?php
							foreach ($queueItems as $queueNumber=>$item)
							{
								$queue_parts = explode('_', $item);
								$order_id = $queue_parts[2];
								$queue_time = intval($queue_parts[3]);
								
								?>
									<tr>
										<td style="text-align: center;"><?=$queueNumber?></td>
										<td style="text-align: center;"><?=$order_id?></td>
										<td><?=date("H:i:s (d/m/y)", $queue_time)?></td>
									</tr>
								<?php
							}
						?>
					</tbody>
				</table>		
				<br>
				<button class="button button-primary"
					onclick="location.href='?page=<?=$page?>&printersettings=<?=$settingsParam?>&cq'">
					Clear Queue
				</button>
			<?php
		}
	}
	
	function star_cloudprnt_render_printer_history_section($page, $settingsParam, $printerData)
	{
		star_cloudprnt_render_settings_section_title("Printed Order History");
		
		$orderHistory = star_cloudprnt_queue_get_order_history();
		
		if (empty($orderHistory))
		{
			echo 'No printed previous orders have been logged.<br>';
			return;
		}
		
		?>
			<table>
				<thead>
					<tr>
						<th style="padding: 5px">Order ID</th>
						<th>Copy Count</th>
						<th>Queued On</th>
						<th>Printed On</th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($orderHistory as $item)
						{
							$exploded = explode('_', $item);
							$copy = intval($exploded[0])+1;
							$order_id = $exploded[2];
							$queue_time = intval($exploded[3]);
							$printed_time = intval($exploded[4]);
							
							?>
								<tr>
									<td style="text-align: center;"><?=$order_id?></td>
									<td style="text-align: center;"><?=$copy?></td>
									<td style="text-align: center;"><?=date("H:i:s (d/m/y)", $queue_time)?></td>
									<td><?=date("H:i:s (d/m/y)", $printed_time)?></td>
								</tr>
							<?php
						}
					?>
				</tbody>
			</table>
			
			<br>
			<button class="button button-primary"
				onclick="location.href='?page=<?=$page?>'&printersettings='<?=$settingsParam?>&coh\'">
				Clear Order History
			</button>';
		<?php		
	}

	function star_cloudprnt_render_settings_error($message)
	{
		?>
			<h2 style="color: red"><?=$message?></h2>
		<?php
	}
	
	function star_cloudprnt_render_settings_section_title($message)
	{
		?>
			<h2><?=$message?></h2>
		<?php
	}

	function star_cloudprnt_render_return_to_settings_link($page)
	{
		?>
			<br><br><a href="?page=<?=$page?>">Return to settings page</a>
		<?php
	}

	function star_cloudprnt_show_printer_settings_page()
	{
		$page = htmlspecialchars($_GET['page']);
		$selectedPrinter = htmlspecialchars(base64_decode($_GET['printersettings']));
		$settingsParam = base64_encode($selectedPrinter);
		$printerList = star_cloudprnt_get_printer_list();
		
		$printerData = null;
		foreach ($printerList as $printer)
		{
			if ($printer['name'] == $selectedPrinter)
			{
				$printerData = $printer;
				break;
			}
		}
		
		if($printerData == null)
		{
			// Render Printer Not Found page
			star_cloudprnt_render_settings_error("Printer Not Found!");
			star_cloudprnt_render_return_to_settings_link($page);

			return;
		}

		// render device information sections
		star_cloudprnt_render_printer_info_section($page, $settingsParam, $printerData);
		star_cloudprnt_render_printer_identification_section($printerData);
		star_cloudprnt_render_printer_interface_section($printerData);
		star_cloudprnt_render_printer_encodings_section($printerData);
		star_cloudprnt_render_printer_queue_section($page, $settingsParam, $printerData);
		star_cloudprnt_render_printer_history_section($page, $settingsParam, $printerData);
		
		star_cloudprnt_render_delete_printer_section($page, $printerData['printerOnline'], $settingsParam);
	}
?>