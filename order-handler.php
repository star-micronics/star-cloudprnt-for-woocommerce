<?php

	/*
	 *	Receipt/ticket rendering functions
	 *
	 *  All functions here are responsible for generating the printed receipt, by analysing a WC_Order
	 *  object to obtain information about the order, and calling printer functions on the $printer object.
	 * 
	*/

	// Always include this - it is responsible for registering the necessary hooks to trigger print jobs
	include_once('order-handler.inc.php');


	// Return the text data used as a separator/horizontal line on the page 
	function star_cloudprnt_get_seperator($max_chars)
	{
		return str_repeat('_', $max_chars);
	}
	

	// iterate through the order line items, rendering each one
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
			
			if ($variation_id != 0)
			{
				$product_variation = new WC_Product_Variation( $variation_id );
				$product_name = $product_variation->get_title();
			}

			if ($alt_name != "")
				$product_name = $alt_name;
			
			$formatted_item_price = star_cloudprnt_format_currency($item_price);
			$formatted_total_price = star_cloudprnt_format_currency($item_total_price);
			
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
						$item_qty." x Cost: ".$formatted_item_price,
						$formatted_total_price), $max_chars));
		}
	}
	
	// Print information about any used coupons
	function star_cloudprnt_print_coupon_info(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$max_chars = $selectedPrinter['columns'];
		$coupons = $order->get_coupon_codes();

		if(empty($coupons))
			return;

		$printer->add_text_line("");

		foreach($coupons as $coupon_code)
		{
			$coupon = new WC_Coupon($coupon_code);
			$coupon_type = $coupon->get_discount_type();
			$coupon_value = "";

			error_log("Coupon Type: " . $coupon_type);

			if($coupon_type == "fixed_cart")
				$coupon_value = star_cloudprnt_get_codepage_currency_symbol() . number_format(-$coupon->get_amount(), 2, '.', '');
			elseif ($coupon_type == "percent")
				$coupon_value = '-' . $coupon->get_amount() . '%';

			$printer->add_text_line(
				star_cloudprnt_get_column_separated_data(
					array("Coupon: " . $coupon_code,
					$coupon_value), 
				$max_chars));
		}
		
	}

	// Generate the Additional Order info section, which prints extra fields that are attached to the order
	// Such as delivery times etc.
	function star_cloudprnt_print_additional_order_info(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		if(get_option('star-cloudprnt-print-order-meta-cb') != "on")
			return;
		
		$max_chars = $selectedPrinter['columns'];
		$meta_data = $order->get_meta_data();

		$is_printed = false;

		$print_hidden = get_option('star-cloudprnt-print-order-meta-hidden') == 'on';
		$excluded_keys = array_map('trim', explode(',', esc_attr(get_option('star-cloudprnt-print-order-meta-exclusions'))));

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

			$formatted_key = $item_data["key"];
			if(get_option('star-cloudprnt-print-order-meta-reformat-keys') == 'on')
				$formatted_key = mb_convert_case(mb_ereg_replace("_", " ", $formatted_key), MB_CASE_TITLE);

			$printer->add_text_line(star_cloudprnt_filter_html($formatted_key) . ": " . star_cloudprnt_filter_html($item_data["value"]));
		}
		
		if($is_printed)	$printer->add_text_line("");
	}

	// Print the address secion - currently this prints the shipping address if present, otherwise
	// falls back to the billing address
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
		$title_text = get_option('star-cloudprnt-print-header-title');

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

		// Annonymous helper function used to format the total lines
		$ft = function($text, $value) use ($printer) {
			$formatted_value = star_cloudprnt_format_currency($value);
			$printer->add_text_line(
				star_mb_str_pad($text, 15)
				. star_mb_str_pad($formatted_value, 10, " ", STR_PAD_LEFT)
				);
		};

		$printer->add_new_line(1);
		$printer->set_text_right_align();

		if($order_meta['_cart_discount'][0] != 0)
			$ft("DISCOUNT", -$order_meta['_cart_discount'][0]);

		if(array_key_exists("_wpslash_tip", $order_meta))
			$ft("TIP", $order_meta['_wpslash_tip'][0]);

		$ft("TOTAL", $order_meta['_order_total'][0]);

		$printer->set_text_left_align();
	}

	// Print info below items list
	function star_cloudprnt_print_items_footer(&$printer, &$selectedPrinter, &$order, &$order_meta)
	{
		$item_footer_message = get_option('star-cloudprnt-print-items-footer-message');

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
		star_cloudprnt_print_coupon_info($printer, $selectedPrinter, $order, $order_meta);
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


?>