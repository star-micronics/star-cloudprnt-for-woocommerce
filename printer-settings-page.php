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
		$selectedPrinter = base64_decode($_GET['printersettings']);
		$printerMac = star_cloudprnt_get_selected_printer_mac($selectedPrinter);

		if (isset($printerMac))
		{
			// Sanitize and escape before saving
			$newPrinterName = esc_attr(sanitize_text_field(base64_decode($_GET['npn'])));
			// Validate
			if (strlen($newPrinterName) < 3 || strlen($newPrinterName) > 35)
			{
				header('location: ?page='.$_GET['page'].'&printersettings='.base64_encode($selectedPrinter).'&errorCode=1');
				return;
			}
			// Save printer data in CloudPRNT
			$printer = new Star_CloudPRNT_Printer($printerMac);
			$printer->updatePrinterData("name", $newPrinterName);
			// Save selected printer in WordPress
			if ($selectedPrinter == get_option('printer-select')) update_option('printer-select', $newPrinterName);
			// Redirect to new printer page
			header('location: ?page='.$_GET['page'].'&printersettings='.base64_encode($newPrinterName));
		}
	}

	function star_cloudprnt_clear_printer_queue()
	{
		$printerMac = star_cloudprnt_get_selected_printer_mac(base64_decode($_GET['printersettings']));

		if (isset($printerMac))
		{
			star_cloudprnt_queue_clear_list($printerMac);
			header('location: ?page='.$_GET['page'].'&printersettings='.$_GET['printersettings']);
		}
	}

	function star_cloudprnt_clear_order_history()
	{
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		$fh = fopen($history_path, 'w');
		fclose($fh);
		header('location: ?page='.$_GET['page'].'&printersettings='.$_GET['printersettings']);
	}

	function star_cloudprnt_delete_printer()
	{
		$printerMac = star_cloudprnt_get_selected_printer_mac(base64_decode($_GET['printersettings']));

		if (isset($printerMac))
		{
			$printer = new Star_CloudPRNT_Printer($printerMac);
			$printer->deletePrinter();
			header('location: ?page='.$_GET['page']);
		}
	}

	function star_cloudprnt_show_printer_settings_page()
	{
		$selectedPrinter = base64_decode($_GET['printersettings']);
		$printerList = star_cloudprnt_get_printer_list();

		$printerdata;
		foreach ($printerList as $printer)
		{
			if ($printer['name'] == $selectedPrinter)
			{
				$printerdata = $printer;
				break;
			}
		}

		?>

        <h2><?php esc_html_e('Printer Information', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<script>
				function showDiv() {
					if (document.getElementById('editPrinterNameContainer').style.display == "block")
					{
						document.getElementById('editPrinterNameContainer').style.display = "none";
                        document.getElementById('changeNameLabel').innerHTML = "<?php esc_html_e('Rename', 'star-cloudprnt-for-woocommerce'); ?>";
					}
					else
					{
						document.getElementById('editPrinterNameContainer').style.display = "block";
                        document.getElementById('changeNameLabel').innerHTML = "<?php esc_html_e('Hide', 'star-cloudprnt-for-woocommerce'); ?>";
					}
				}
				function savePrinterName()
				{
					var newName = document.getElementById("printerName").value;
					window.location.href = '?page=<?php echo $_GET['page']; ?>&printersettings=<?php echo $_GET['printersettings']; ?>&npn='+btoa(newName);
				}
			</script>
			<?php
				$onlineString = '<span style="color: orange">'.esc_html__('Unknown', 'star-cloudprnt-for-woocommerce').'</span>';
				if ($printerdata['printerOnline']) $onlineString = '<span style="color: green">'.esc_html__('Connected', 'star-cloudprnt-for-woocommerce').'</span>';
				else $onlineString = '<span style="color: red">'.esc_html__('Not Connected', 'star-cloudprnt-for-woocommerce').'</span>';
				$httpStatus = str_replace("%", " ", $printerdata['statusCode']);
				$httpStatus = str_replace(" 20", " ", $httpStatus);

				echo "<strong>".esc_html__('Name:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['name'].' - <a id="changeNameLabel" href="javascript:void(0);" onclick=\'showDiv()\'>'.esc_html__('Rename', 'star-cloudprnt-for-woocommerce').'</a><br>';
				echo '<div id="editPrinterNameContainer" style="display: none">';
					echo '<input id="printerName" type="text" name="printer" id="nprinter" placeholder="'.$printerdata['name'].'" value="'.$printerdata['name'].'" autocomplete="off">';
					echo '<a href="javascript: void(0);" onclick="savePrinterName()" style="padding-left: 10px">'.esc_html__('Save', 'star-cloudprnt-for-woocommerce').'</a>';
				echo '</div>';
				if (isset($_GET['errorCode']) && $_GET['errorCode'] == 1) echo '<script type="text/javascript">showDiv()</script><span style="color:red;">'.esc_html__('Error: The new printer name must be between 3 and 35 characters long.', 'star-cloudprnt-for-woocommerce').'</span><br>';
				echo "<strong>".esc_html__('Poll Interval:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['GetPollInterval']."<br>";
				echo "<strong>".esc_html__('Connectivity:', 'star-cloudprnt-for-woocommerce')."</strong> ".$onlineString."<br>";
				echo "<strong>".esc_html__('ASB Status Code:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['status']."<br>";
				echo "<strong>".esc_html__('HTTP Status Code:', 'star-cloudprnt-for-woocommerce')."</strong> ".$httpStatus."<br>";
				echo "<strong>".esc_html__('Last Communication:', 'star-cloudprnt-for-woocommerce')."</strong> ".date("D j M y - H:i:s", $printerdata['lastActive']);
			?>

			<h2><?php esc_html_e('Printer Identification', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				echo "<strong>".esc_html__('MAC Address:', 'star-cloudprnt-for-woocommerce')."</strong> ".strtoupper($printerdata['printerMAC'])."<br>";
				echo "<strong>".esc_html__('IP Address:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['ipAddress'];
			?>

			<h2><?php esc_html_e('Interface', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				echo "<strong>".esc_html__('Client Type:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['ClientType']."<br>";
				echo "<strong>".esc_html__('Client Version:', 'star-cloudprnt-for-woocommerce')."</strong> ".$printerdata['ClientVersion'];
			?>

			<h2><?php esc_html_e('Supported Encodings', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				$encodings = explode(';', $printerdata['Encodings']);
				foreach ($encodings as $encoding)
				{
					echo $encoding."<br>";
				}
			?>

			<h2><?php esc_html_e('Printer Queue', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				$queueItems = star_cloudprnt_queue_get_queue_list($printerdata['printerMAC']);
				if (empty($queueItems)) echo esc_html__('No items found in printer queue.', 'star-cloudprnt-for-woocommerce').'<br>';
				else
				{
					echo '<table>';
					echo '<tr>';
						echo '<th style="padding: 5px">'.esc_html__('Priority', 'star-cloudprnt-for-woocommerce').'</th>';
						echo '<th>'.esc_html__('Order ID', 'star-cloudprnt-for-woocommerce').'</th>';
						echo '<th>'.esc_html__('Queued On', 'star-cloudprnt-for-woocommerce').'</th>';
					echo '</tr>';
						foreach ($queueItems as $queueNumber=>$item)
						{
							$queue_parts = explode('_', $item);
							$order_id = $queue_parts[2];
							$queue_time = intval($queue_parts[3]);
							echo '<tr>';
								echo '<td style="text-align: center;">'.$queueNumber.'</td>';
								echo '<td style="text-align: center;">'.$order_id.'</td>';
								echo '<td>'.date("H:i:s (d/m/y)", $queue_time).'</td>';
							echo '</tr>';
						}
					echo '</table>';

					echo '<br><button class="button button-primary" onclick="location.href=\'?page='.$_GET['page']
							.'&printersettings='.$_GET['printersettings'].'&cq\'">'.esc_html__('Clear Queue', 'star-cloudprnt-for-woocommerce').'</button>';
				}

			?>

			<h2><?php esc_html_e('Printed Order History', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				$orderHistory = star_cloudprnt_queue_get_order_history();
				if (empty($orderHistory)) echo esc_html__('No printed previous orders have been logged.', 'star-cloudprnt-for-woocommerce').'<br>';
				else
				{
					echo '<table>';
					echo '<tr>';
						echo '<th style="padding: 5px">'.esc_html__('Order ID', 'star-cloudprnt-for-woocommerce').'</th>';
						echo '<th>'.esc_html__('Copy Count', 'star-cloudprnt-for-woocommerce').'</th>';
						echo '<th>'.esc_html__('Queued On', 'star-cloudprnt-for-woocommerce').'</th>';
						echo '<th>'.esc_html__('Printed On', 'star-cloudprnt-for-woocommerce').'</th>';
					echo '</tr>';
						foreach ($orderHistory as $item)
						{
							$exploded = explode('_', $item);
							$copy = intval($exploded[0])+1;
							$order_id = $exploded[2];
							$queue_time = intval($exploded[3]);
							$printed_time = intval($exploded[4]);

							echo '<tr>';
								echo '<td style="text-align: center;">'.$order_id.'</td>';
								echo '<td style="text-align: center;">'.$copy.'</td>';
								echo '<td style="text-align: center;">'.date("H:i:s (d/m/y)", $queue_time).'</td>';
								echo '<td>'.date("H:i:s (d/m/y)", $printed_time).'</td>';
							echo '</tr>';
						}
					echo '</table>';

					echo '<br><button class="button button-primary" onclick="location.href=\'?page='.$_GET['page']
							.'&printersettings='.$_GET['printersettings'].'&coh\'">'.esc_html__('Clear Order History', 'star-cloudprnt-for-woocommerce').'</button>';
				}
			?>

			<h2><?php esc_html_e('Delete Printer', 'star-cloudprnt-for-woocommerce'); ?></h2>
			<?php
				if ($printerdata['printerOnline']) echo '<span style="color: red"><span class="dashicons dashicons-no"></span>'.esc_html__('You cannot delete the printer whilst it is connected', 'star-cloudprnt-for-woocommerce').'</span>';
				else echo '<button class="button button-primary" onclick="location.href=\'?page='.$_GET['page']
						.'&printersettings='.$_GET['printersettings'].'&dp\'">'.esc_html__('Delete Printer', 'star-cloudprnt-for-woocommerce').'</button>';
			?>

			<br><br><a href="?page=<?php echo $_GET['page']; ?>"><?php esc_html_e('Return to previous page', 'star-cloudprnt-for-woocommerce'); ?></a>
		<?php
	}
?>
