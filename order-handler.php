<?php
	mb_internal_encoding('utf-8');

	function star_mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT) {
    $str_len = mb_strlen($str);
    $pad_str_len = mb_strlen($pad_str);
    if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
        $str_len = 1; // @debug
    }
    if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
        return $str;
    }
   
    $result = null;
    $repeat = ceil($str_len - $pad_str_len + $pad_len);
    if ($dir == STR_PAD_RIGHT) {
        $result = $str . str_repeat($pad_str, $repeat);
        $result = mb_substr($result, 0, $pad_len);
    } else if ($dir == STR_PAD_LEFT) {
        $result = str_repeat($pad_str, $repeat) . $str;
        $result = mb_substr($result, -$pad_len);
    } else if ($dir == STR_PAD_BOTH) {
        $length = ($pad_len - $str_len) / 2;
        $repeat = ceil($length / $pad_str_len);
        $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
                  	. $str
                    . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
    }
   
    return $result;
	}

	function star_cloudprnt_get_column_separated_data($columns, $max_chars)
	{
		$total_columns = count($columns);
		
		if ($total_columns == 0) return "";
		if ($total_columns == 1) return $columns[0];
		if ($total_columns == 2)
		{
			//$total_characters = strlen($columns[0])+strlen($columns[1]);
			$total_characters = mb_strwidth($columns[0]) + mb_strwidth($columns[1]);
			$total_whitespace = $max_chars - $total_characters;
			if ($total_whitespace < 0) return "";
			return $columns[0].str_repeat(" ", $total_whitespace).$columns[1];
		}
		
		$total_characters = 0;
		foreach ($columns as $column)
		{
			$total_characters += strlen($column);
		}
		$total_whitespace = $max_chars - $total_characters;
		if ($total_whitespace < 0) return "";
		$total_spaces = $total_columns-1;
		$space_width = floor($total_whitespace / $total_spaces);
		$result = $columns[0].str_repeat(" ", $space_width);
		for ($i = 1; $i < ($total_columns-1); $i++)
		{
			$result .= $columns[$i].str_repeat(" ", $space_width);
		}
		$result .= $columns[$total_columns-1];
		
		return $result;
	}
	

	function star_cloudprnt_get_seperator($max_chars)
	{
		return str_repeat('_', $max_chars);
	}
	

	function star_cloudprnt_get_wc_order_notes($order_id){
		//make sure it's a number
		$order_id = intval($order_id);
		//get the post 
		$post = get_post($order_id);
		//if there's no post, return as error
		if (!$post) return false;

		return $post->post_excerpt;
	}


	function star_cloudprnt_get_codepage_currency_symbol()
	{
		$encoding = get_option('star-cloudprnt-printer-encoding-select');
		$symbol = get_woocommerce_currency_symbol();

		return star_cloudprnt_filter_html($symbol);
	}
	
	
	function star_cloudprnt_filter_html($data)
	{
		/* Filter known html key words, convert to printer appropriate commands and encoding */
		
		$encoding = get_option('star-cloudprnt-printer-encoding-select');

		$phpenc = "UTF-8";
		if($encoding === "1252")
			$phpenc = "cp1252";

		$data = html_entity_decode($data, ENT_QUOTES, "UTF-8");

		if($phpenc !== "UFT-8")
			$data = mb_convert_encoding($data, $phpenc, "UTF-8");

		$data = str_replace(array("\r", "\n"), '', $data);				// Strip newlines

		return strip_tags($data);
	}

	function star_cloudprnt_print_items($printer, $selectedPrinter, $order, $order_meta)
	{
		$max_chars = $selectedPrinter['columns'];
		$order_items = $order->get_items();
		foreach ($order_items as $item_id => $item_data)
		{
			$product_name = $item_data['name'];
			$product_id = $item_data['product_id'];
			$variation_id = $item_data['variation_id'];
			$product = wc_get_product($product_id);

			$alt_name = $product->get_attribute( 'star_cp_print_name' );				// Custom attribute can be used to override the product name on receipt

			$item_qty = wc_get_order_item_meta($item_id, "_qty", true);
			
			$item_total_price = floatval(wc_get_order_item_meta($item_id, "_line_total", true))
				+floatval(wc_get_order_item_meta($item_id, "_line_tax", true));
			
			$item_price = floatval($item_total_price) / intval($item_qty);
			$currencyHex = star_cloudprnt_get_codepage_currency_symbol();
			
			if ($variation_id != 0)
			{
				$product_variation = new WC_Product_Variation( $variation_id );
				$product_name = $product_variation->get_title();
			}

			if ($alt_name != "")
				$product_name = $alt_name;
			
			$formatted_item_price = number_format($item_price, 2, '.', '');
			$formatted_total_price = number_format($item_total_price, 2, '.', '');
			
			$printer->set_text_emphasized();
			$printer->add_text_line(star_cloudprnt_filter_html($product_name." - ID: ".$product_id.""));
			$printer->cancel_text_emphasized();
			
			$meta = $item_data->get_formatted_meta_data("_", TRUE);

			foreach ($meta as $meta_key => $meta_item)
			{
				// Use $meta_item->key for the raw (non display formatted) key name
				$printer->add_text_line(star_cloudprnt_filter_html(" ".$meta_item->display_key.": ".$meta_item->display_value));
			}
			
			$printer->add_text_line(star_cloudprnt_get_column_separated_data(array(" Qty: ".
						$item_qty." x Cost: ".$currencyHex.$formatted_item_price,
						$currencyHex.$formatted_total_price), $max_chars));
		}
	}
	
	function star_cloudprnt_print_additional_order_info(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		if(get_option('star-cloudprnt-print-order-meta-cb') != "on")
			return;
		
		$max_chars = $selectedPrinter['columns'];
		$meta_data = $order->get_meta_data();

		$is_printed = false;

		$print_hidden = get_option('star-cloudprnt-print-order-meta-hidden') == "on";
		$excluded_keys = array_map('trim', explode(',', get_option('star-cloudprnt-print-order-meta-exclusions')));

		foreach ($meta_data as $item_id => $meta_data_item)
		{
			$item_data = $meta_data_item->get_data();

			// Skip any keys in the exclusion list
			if(in_array($item_data["key"], $excluded_keys))
				continue;

			// Skip hidden fields (any field whose key begins with a "_", by convention)
			if(!$print_hidden && mb_substr($item_data["key"], 0, 1) == "_")
				continue;

			if(! $is_printed)
			{
				$is_printed = true;
				$printer->set_text_emphasized();
				$printer->add_text_line("Additional Order Information");
				$printer->cancel_text_emphasized();
			}

			$printer->add_text_line(star_cloudprnt_filter_html($item_data["key"]) . ": " . star_cloudprnt_filter_html($item_data["value"]));
		}
		
		if($is_printed)	$printer->add_text_line("");
	}

	function star_cloudprnt_print_address($printer, $selectedPrinter, $order, $order_meta)
	{
		// function to get address values if they exist or return an empty string
		$gkv = function($key) use ($order_meta) {
			if(array_key_exists($key, $order_meta))
				return $order_meta[$key][0];

			return '';
		};

		$fname = $gkv('_shipping_first_name');
		$lname = $gkv('_shipping_last_name');
		$a1 = $gkv('_shipping_address_1');
		$a2 = $gkv('_shipping_address_2');
		$city = $gkv('_shipping_citye', $order_meta);
		$state = $gkv('_shipping_state');
		$postcode = $gkv('_shipping_postcode');
		$tel = $gkv('_billing_phone');
		
		$printer->set_text_emphasized();
		if ($a1 == '')
		{
			$printer->add_text_line("Billing Address:");
			$printer->cancel_text_emphasized();
			$fname = $gkv('_billing_first_name');
			$lname = $gkv('_billing_last_name');
			$a1 = $gkv('_billing_address_1');
		
			$a2 = $gkv('_billing_address_2');
			$city = $gkv('_billing_city');
			$state = $gkv('_billing_state');
			$postcode = $gkv('_billing_postcode');
		}
		else
		{
			$printer->add_text_line("Shipping Address:");
			$printer->cancel_text_emphasized();
		}
		
		$printer->add_text_line($fname." ".$lname);
		$printer->add_text_line($a1);
		if ($a2 != '') $printer->add_text_line($a2);
		if ($city != '') $printer->add_text_line($city);
		if ($state != '') $printer->add_text_line($state);
		if ($postcode != '') $printer->add_text_line($postcode);

		$printer->add_text_line("Tel: ".$tel);
	}
	
	// Generate the receipt header
	function star_cloudprnt_print_receipt_header(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$order_number = $order->get_order_number();			// Displayed order number may be different to order_id when using some plugins
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		// Print a top logo if configured
		if (get_option('star-cloudprnt-print-logo-top-input'))
			$printer->add_nv_logo(esc_attr(get_option('star-cloudprnt-print-logo-top-input')));

		// Top of page Title
		$title_text = "ORDER NOTIFICATION";

		// Set formatting for title
		$printer->set_text_emphasized();
		$printer->set_text_center_align();
		$printer->set_font_magnification(2, 2);

		// Printthe title - word wrapped (NOTE - php wordwrap() is not multi-byte aware, and definitely not half/full width character aware)
		$printer->add_text_line(wordwrap($title_text, $selectedPrinter['columns']/2, "\n", true)); // Columns are divided by 2 because we are using double width characters.

		// Reset text formatting
		$printer->set_text_left_align();
		$printer->cancel_text_emphasized();
		$printer->set_font_magnification(1, 1);

		// Print sub-header
		$printer->add_new_line(1);
		$printer->add_text_line(star_cloudprnt_get_column_separated_data(
			array(
				"Order #".$order_number, 
				date("{$date_format} {$time_format}", current_time('timestamp'))), 
				$selectedPrinter['columns']));

		// Print header info area
		$printer->add_new_line(1);
		$printer->add_text_line("Order Status: ".$order->get_status());
		$order_date = date("{$date_format} {$time_format}", $order->get_date_created()->getOffsetTimestamp());
		$printer->add_text_line("Order Date: {$order_date}");	
		
		if (isset($shipping_items['name']))
		{
			$printer->add_new_line(1);
			$printer->add_text_line("Shipping Method: ".$shipping_items['name']);
		}
		$printer->add_text_line("Payment Method: ".$order_meta['_payment_method_title'][0]);
	}

	// Print heading above the items list
	function star_cloudprnt_print_items_header(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$printer->add_new_line(1);
		$printer->add_text_line(star_cloudprnt_get_column_separated_data(array('ITEM', 'TOTAL'), $selectedPrinter['columns']));
		$printer->add_text_line(star_cloudprnt_get_seperator($selectedPrinter['columns']));
	}

	// Print totals
	function star_cloudprnt_print_item_totals(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$printer->add_new_line(1);
		$printer->set_text_right_align();
		$formatted_overall_total_price = number_format($order_meta['_order_total'][0], 2, '.', '');
		$printer->add_text_line("TOTAL     ".star_cloudprnt_get_codepage_currency_symbol().$formatted_overall_total_price);
		$printer->set_text_left_align();
	}

	// Print info below items list
	function star_cloudprnt_print_items_footer(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$item_footer_message = "All prices are inclusive of tax (if applicable).";

		$printer->add_new_line(1);
		$printer->add_text_line(wordwrap($item_footer_message, $selectedPrinter['columns'], "\n", true));
		$printer->add_new_line(1);
	}

	// Print info below items list
	function star_cloudprnt_print_customer_notes(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$notes = star_cloudprnt_get_wc_order_notes($order->get_id());

		$printer->add_new_line(1);
		$printer->set_text_emphasized();
		$printer->add_text_line("Customer Provided Notes:");
		$printer->cancel_text_emphasized();
		
		if(empty($notes))
			$printer->add_text_line('None');
		else
			$printer->add_text_line(wordwrap($notes, $selectedPrinter['columns'], "\n", true));
	}

	// Generate the receipt header
	function star_cloudprnt_print_receipt_footer(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		// Print a bottom logo if configured
		if (get_option('star-cloudprnt-print-logo-bottom-input'))
			$printer->add_nv_logo(esc_attr(get_option('star-cloudprnt-print-logo-bottom-input')));
		
	}

	function star_cloudprnt_print_order_summary($selectedPrinter, $file, $order_id)
	{

		$order = wc_get_order($order_id);
		$order_number = $order->get_order_number();			// Displayed order number may be different to order_id when using some plugins
		$shipping_items = @array_shift($order->get_items('shipping'));
		$order_meta = get_post_meta($order_id);
		
		$meta_data = $order->get_meta_data();
		
		// Get the correct object for building commands for the selected printer
		$printer = star_cloudprnt_command_generator($selectedPrinter, $file);
		
		// Ask the priter to use the correct text encoding
		$printer->set_codepage(get_option('star-cloudprnt-printer-encoding-select'));

		/*
		 *		Generate printer receipt/ticket data
		*/
		star_cloudprnt_print_receipt_header($printer, $selectedPrinter, $order, $order_meta);
		
		star_cloudprnt_print_items_header($printer, $selectedPrinter, $order, $order_meta);
		star_cloudprnt_print_items($printer, $selectedPrinter, $order, $order_meta);
		star_cloudprnt_print_item_totals($printer, $selectedPrinter, $order, $order_meta);
		star_cloudprnt_print_items_footer($printer, $selectedPrinter, $order, $order_meta);
		
		star_cloudprnt_print_additional_order_info($printer, $selectedPrinter, $order, $order_meta);
		star_cloudprnt_print_address($printer, $selectedPrinter, $order, $order_meta);
		star_cloudprnt_print_customer_notes($printer, $selectedPrinter, $order, $order_meta);

		star_cloudprnt_print_receipt_footer($printer, $selectedPrinter, $order, $order_meta);

		// Get the number of copies from the settings
		$copies=intval(get_option("star-cloudprnt-print-copies-input"));
		if($copies < 1) $copies = 1;
		
		// Send the print job to the spooling folder, ready to be connected by the printer and printed.
		$printer->printjob($copies);
	}

	// Get printer compatibility information and print
	function star_cloudprnt_trigger_print($order_id)
	{

		$extension = STAR_CLOUDPRNT_SPOOL_FILE_FORMAT;	
		
		$selectedPrinterMac = "";
		$selectedPrinter = array();
		$printerList = star_cloudprnt_get_printer_list();
		if (!empty($printerList))
		{
		
			foreach ($printerList as $printer)
			{
				if (get_option('star-cloudprnt-printer-select') == $printer['name'])
				{
					$selectedPrinter = $printer;
					$selectedPrinterMac = $printer['printerMAC'];
					break;
				}
			}
			
			if (sizeof($selectedPrinter) == 0) {
				$selectedPrinter = $printerList[0];
			}
			
			/* Decide best printer emulation and print width as far as possible
			   NOTE: this is not the ideal way, but suits the existing
			   code structure. Will be reviewed.
			   */
			
			$encodings = $selectedPrinter['Encodings'];
			$columns = STAR_CLOUDPRNT_MAX_CHARACTERS_THREE_INCH;
			if (strpos($encodings, "application/vnd.star.line;") !== false) {
				/* There is no guarantee that printers will always return zero spacing between
				   the encoding name and separating semi-colon. But, definitely the HIX does, socket_accept
				   this is enough to ensure that thermal print mode is always used on HIX printers
				   with pre 1.5 firmware. This matches older plugin behaviour and therefore
				   avoids breaking customer sites.
				*/
				$extension = "slt";
			} else if (strpos($encodings, "application/vnd.star.linematrix") !== false) {
				$extension = "slm";
				$columns = STAR_CLOUDPRNT_MAX_CHARACTERS_DOT_THREE_INCH;
			} else if (strpos($encodings, "application/vnd.star.line") !== false) {
				// a second check for Line mode - just in case the above one didn't catch item
				// and after the "linemodematrix" check, to avoid a false match.
				$extension = "slt";
			} else if (strpos($encodings, 'application/vnd.star.starprnt') !== false) {
				$extension = "spt";
			} else if (strpos($encodings, "text/plain") !== false) {
				$extension = "txt";
			} 
			
			if ($selectedPrinter['ClientType'] == "Star mC-Print2") {
				$columns = STAR_CLOUDPRNT_MAX_CHARACTERS_TWO_INCH;
			}
			
			$selectedPrinter['format'] = $extension;
			$selectedPrinter['columns'] = $columns;
			
			$file = STAR_CLOUDPRNT_PRINTER_PENDING_SAVE_PATH.star_cloudprnt_get_os_path("/order_".$order_id."_".time().".".$extension);

			if ($selectedPrinter !== "") star_cloudprnt_print_order_summary($selectedPrinter, $file, $order_id);
		}
	}
	
	// Insert the "Reprint vis Star CloudPRNT" order action to the list
	function star_cloudprnt_order_reprint_action( $actions ) {
		global $theorder;

		$actions['star_cloudprnt_reprint_action'] = __( 'Print via Star CloudPRNT', 'my-textdomain' );
		return $actions;
	}

	// Handle print requests issues with WC_Order object (used for the reprinting order action)
	function star_cloudprnt_reprint($order)
	{
		star_cloudprnt_trigger_print($order->get_id());
	}

	// Register a meta box on the edit order sidebar
	function star_cloudprnt_order_meta_boxes()
	{
    add_meta_box(
        'woocommerce-order-star-cloudprnt',
        __( 'Star CloudPRNT' ),
        'star_cloudprnt_order_meta_box_content',
        'shop_order',
        'side',
        'default'
    );
	}

	// Render the sidebar metabox on the Edit Order page
	function star_cloudprnt_order_meta_box_content()
	{
		?>
			<script type="text/javascript" >
				function star_cloudprnt_trigger($) {

					var data = {
						'action': 'star_cloudprnt_reprint_action',
						'order_id': '<?= $_GET["post"] ?>'
					};

					jQuery.post(ajaxurl, data, function(response) {
						//alert('Got this from the server: ' + response);
						jQuery("#star_cp_job_sent").show();
						setTimeout(() => {
							jQuery("#star_cp_job_sent").hide();
						}, 2000);
					});
				}

			</script>

			<a href="javascript: star_cloudprnt_trigger();"><span>Print with Star CloudPRNT</span></a> <span id="star_cp_job_sent" style="display:none">✔</span>

		<?php
	}

	// Handle the ajax reprint action
	function star_cloudprnt_reprint_button_callback()
	{
		star_cloudprnt_trigger_print($_POST["order_id"]);
		wp_die();
	}

	function star_cloudprnt_setup_order_handler()
	{
		if (selected(get_option('star-cloudprnt-select'), "enable", false) !== "")
		{
			// Add reprint order action
			add_action( 'woocommerce_order_actions', 'star_cloudprnt_order_reprint_action' );

			// Register handler for ajax reprint request action (used by the "Print with Star CloudPRNT" button in the sidebar metabox)
			add_action( 'wp_ajax_star_cloudprnt_reprint_action', 'star_cloudprnt_reprint_button_callback' );

			// Register a sidebar metabox to be displayed on the Edit Order page, used to host a "print" button.
			add_action( 'add_meta_boxes', 'star_cloudprnt_order_meta_boxes' );

			// Register handler for the order action reprint request
			add_action('woocommerce_order_action_star_cloudprnt_reprint_action', 'star_cloudprnt_reprint', 1, 1 );
			

			// Register the automatic order printing trigger, depending on config
			$trigger = get_option('star-cloudprnt-trigger');
			if($trigger === 'thankyou') {
				add_action('woocommerce_thankyou', 'star_cloudprnt_trigger_print', 1, 1);
			} elseif ($trigger === 'status_processing') {
				add_action('woocommerce_order_status_processing', 'star_cloudprnt_trigger_print', 1, 1);
			} elseif ($trigger === 'status_completed') {
				add_action('woocommerce_order_status_completed', 'star_cloudprnt_trigger_print', 1, 1);
			}

			
		}
	}
?>