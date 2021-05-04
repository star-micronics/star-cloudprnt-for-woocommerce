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
	function star_cloudprnt_get_separator($max_chars)
	{
		return str_repeat('_', $max_chars);
	}
	
	// Generate the receipt header
	function star_cloudprnt_print_receipt_header(&$printer, &$order)
	{
		// Print a top logo if configured
		if (get_option('star-cloudprnt-print-logo-top-input'))
		{
			$top_logo = esc_attr(get_option('star-cloudprnt-print-logo-top-input'));
			$top_logo = apply_filters('smcpfw_header_logo', $top_logo, $order);
			if(!empty($top_logo))
				$printer->add_nv_logo($top_logo);
		}

		// Top of page Title
		$title_text = get_option('star-cloudprnt-print-header-title');
		$title_text = apply_filters('smcpfw_header_title', $title_text, $order);

		if(!empty($title_text))
		{
			$printer->clear_formatting();
			$result = apply_filters('smcpfw_render_header_title', $title_text, $printer, $order);

			if($result !== true) {
				// Set formatting for title
				$printer->set_text_emphasized();
				$printer->set_text_center_align();
				$printer->set_font_magnification(2, 2);

				// Print the title
				$printer->add_word_wrapped_text_line($title_text);

				// Reset text formatting
				$printer->clear_formatting();
				$printer->add_new_line(1);
			}
		}
	}

	// Generate the receipt sub-header
	function star_cloudprnt_print_receipt_sub_header(&$printer, &$order)
	{
		$order_number = $order->get_order_number();			// Displayed order number may be different to order_id when using some plugins
		$shipping_items = @array_shift($order->get_items('shipping'));
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		// Anonymous "Print wrapped" helper function used to print wrapped line
		$pw = function($key, $value) use ($printer, $order) {
			$pair = array();
			$pair["key"] = $key;
			$pair["value"] = $value;

			$pair = apply_filters('smcpfw_sub_header_info', $pair, $order);

			$printer->clear_formatting();
			if(apply_filters('smcpfw_render_sub_header_info', $pair, $printer, $order) !== true){
				$printer->add_word_wrapped_text_line($pair["key"] . ": " . $pair["value"]);
			}
		};

		$banner = array();
		$banner["left"] = "Order #".$order_number;			// Order number text to print
		$banner["right"] = date("{$date_format} {$time_format}", current_time('timestamp'));			// TIme of printing text

		$banner = apply_filters('smcpfw_sub_header_banner', $banner, $order);

		$printer->clear_formatting();
		$rendered = apply_filters('smcpfw_render_sub_header_banner', $banner, $printer, $order);

		if($rendered !== true) {
			$printer->add_two_columns_text_line($banner["left"], $banner["right"]);
			$printer->add_new_line(1);
		}

		// Print header info area
		$pw("Order Status", $order->get_status());
		$order_date = date("{$date_format} {$time_format}", $order->get_date_created()->getOffsetTimestamp());
		$pw("Order Date", $order_date);	

		$printer->add_new_line(1);
		if (isset($shipping_items['name']))
			$pw("Shipping Method", $shipping_items['name']);
		
		$pw("Payment Method", $order->get_payment_method_title());
	}

	// Print heading above the items list
	function star_cloudprnt_print_items_header(&$printer, &$order)
	{
		$printer->add_new_line(1);
		$printer->add_two_columns_text_line('ITEM', 'TOTAL');
		$printer->add_text_line(star_cloudprnt_get_separator($printer->get_text_columns()));
	}

	function star_cloudprnt_item_main_data(&$order, &$item)
	{
		$data = array();
		$data["right"] = null;

		$product_name = $item['name'];
		$product_id = $item['product_id'];
		$variation_id = $item['variation_id'];
		$product = wc_get_product($product_id);

		$sku = $product->get_sku();
		$alt_name = $product->get_attribute( 'star_cp_print_name' );				// Custom attribute can be used to override the product name on receipt

		if ($variation_id != 0)																							// Use variation info if it exists
		{
			$product_variation = new WC_Product_Variation( $variation_id );
			$product_name = $product_variation->get_title();
			$sku = $product_variation->get_sku();
			$item_price = $product_variation->get_price();
		}

		if ($alt_name != "")																								// Use alt name for printing if one has been set
			$product_name = $alt_name;

		$product_info = "";
		if(get_option('star-cloudprnt-print-items-print-id') == "on")
			$product_info .= " - ID: " . $product_id;
		if(get_option('star-cloudprnt-print-items-print-sku') == "on" && (! empty($sku)))
			$product_info .= " - SKU: " . $sku;

		if(get_option('star-cloudprnt-print-items-print-tax-code') == "on"){
			$tc = $item->get_tax_class();
			if(!empty($tc)) $product_info .= "  - Tax: " . $tc;
		}

		$data["left"] = star_cloudprnt_filter_html($product_name . $product_info);


		return $data;
	}

	function star_cloudprnt_print_item_main($data, &$printer, &$order, &$item)
	{
		$left = ""; $right = "";
		if(array_key_exists("left", $data))
			$left = $data["left"];

		if(array_key_exists("right", $data))
			$right = $data["right"];

		$printer->set_text_emphasized();
		$printer->add_two_columns_text_line($left, $right);
		$printer->cancel_text_emphasized();
	}

	function star_cloudprnt_item_info(&$order, &$item)
	{
		$data = array();

		$meta = $item->get_formatted_meta_data("_", TRUE);

		foreach ($meta as $meta_key => $meta_item)
		{
			$info = array();
			$info["key"] = $meta_key;
			$info["value"] = $meta_item->value;
			$info["print_key"] = star_cloudprnt_filter_html($meta_item->display_key);
			$info["print_value"] = star_cloudprnt_filter_html($meta_item->display_value);

			// Filter per info field
			$info = apply_filters('smcpfw_items_item_info_field', $info, $item);

			array_push($data, $info);
		}

		// filter whole info field array (plugins can re-order, add extras etc.)
		$data = apply_filters('smcpfw_items_item_info_fields', $data, $item);

		return $data;
	}

	function star_cloudprnt_print_item_info($data, &$printer, &$order, &$item)
	{
		foreach($data as $info) {
			$printer->clear_formatting();
			if(apply_filters('smcpfw_render_items_item_info_field', $info, $printer, $item) !== true)
			{
				$key = "";
				$value = "";
				if(array_key_exists("print_key", $info))
					$key = $info["print_key"] . ": ";
				if(array_key_exists("print_value", $info))
					$value = $info["print_value"];
				
				$printer->add_word_wrapped_text_line(" {$key}{$value}");
			}
		}
	}

	function star_cloudprnt_item_summary_data(&$order, &$item)
	{
		$data = array();

		$variation_id = $item['variation_id'];
		$product = wc_get_product($item['product_id']);
		$item_qty = $item->get_quantity();

		// $item_total_price = floatval(wc_get_order_item_meta($item_id, "_line_total", true))
		// 	+floatval(wc_get_order_item_meta($item_id, "_line_tax", true));


		// Get line price, with or without tax
		$item_total_price = floatval(wc_get_order_item_meta($item->get_id(), "_line_total", true));
		if(get_option('star-cloudprnt-print-items-price-includes-tax') == "on")
			$item_total_price += floatval(wc_get_order_item_meta($item->get_id(), "_line_tax", true));

		$item_price = $product->get_price();																// product unit price

		if ($variation_id != 0)																							// Use variation info if it exists
		{
			$product_variation = new WC_Product_Variation( $variation_id );
			$item_price = $product_variation->get_price();
		}

		$formatted_item_price = star_cloudprnt_format_currency($item_price);
		$formatted_total_price = star_cloudprnt_format_currency($item_total_price);

		$data["left"] = "{$item_qty} x Cost: {$formatted_item_price}";
		$data["right"] = $formatted_total_price;

		return $data;
	}

	function star_cloudprnt_print_item_summary($data, $printer, $order, $item)
	{
		$left = ""; $right = "";
		if(array_key_exists("left", $data))
			$left = $data["left"];

		if(array_key_exists("right", $data))
			$right = $data["right"];

		$printer->add_two_columns_text_line($left, $right);
	}


	// iterate through the order line items, rendering each one
	function star_cloudprnt_print_items(&$printer, &$order)
	{
		$order_items = $order->get_items();
		foreach ($order_items as $item_id => $item)
		{
			$printer->clear_formatting();
			apply_filters('smcpfw_render_items_pre_item', $printer, $item);

			/* 
			 * Item Main Section (Item name, SKU etc)
			*/

			$data = star_cloudprnt_item_main_data($order, $item);
			$data = apply_filters('smcpfw_items_item_main', $data, $item);

			if($data != null)
			{
				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_pre_item_main', $printer, $item);

				$printer->clear_formatting();
				if(apply_filters('smcpfw_render_items_item_main', false, $printer, $item) !== true)
					star_cloudprnt_print_item_main($data, $printer, $order, $item);

				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_post_item_main', $printer, $item);
			}

			/*
			 * Item info section (order item metadata, addons etc.)
			 */

			$data = star_cloudprnt_item_info($order, $item);

			if($data != null)
			{
				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_pre_item_info', $printer, $item);

				$printer->clear_formatting();
				if(apply_filters('smcpfw_render_items_item_info', false, $printer, $item) !== true)
					star_cloudprnt_print_item_info($data, $printer, $order, $item);

				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_post_item_info', $printer, $item);
			}

			/*
			 * Item summary line (qty, unit price, total price etc.)
			 */

			$data = star_cloudprnt_item_summary_data($order, $item);

			if($data != null)
			{
				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_pre_item_summary', $printer, $item);

				$printer->clear_formatting();
				if(apply_filters('smcpfw_render_items_item_summary', false, $printer, $item) !== true)
					star_cloudprnt_print_item_summary($data, $printer, $order, $item);

				$printer->clear_formatting();
				apply_filters('smcpfw_render_items_post_item_summary', $printer, $item);
			}

			$printer->clear_formatting();
			apply_filters('smcpfw_render_items_post_item', $printer, $item);
		}
	}

	// Print information about any used coupons
	function star_cloudprnt_print_coupon_info(&$printer, &$order)
	{
		$coupons = $order->get_coupon_codes();

		if(empty($coupons))
			return;

		$printer->add_text_line("");

		foreach($coupons as $coupon_code)
		{
			$coupon = new WC_Coupon($coupon_code);
			$coupon_type = $coupon->get_discount_type();
			$coupon_value = "";

			if($coupon_type == "fixed_cart")
				$coupon_value = star_cloudprnt_get_codepage_currency_symbol() . number_format(-$coupon->get_amount(), 2, '.', '');
			elseif ($coupon_type == "percent")
				$coupon_value = '-' . $coupon->get_amount() . '%';

			$printer->add_two_columns_text_line("Coupon: " . $coupon_code, $coupon_value);
		}
		
	}

	// Print totals
	function star_cloudprnt_print_item_totals(&$printer, &$order)
	{
		// Anonymous helper function used to format the total lines
		$ft = function($text, $value) use ($printer) {
			$formatted_value = star_cloudprnt_format_currency($value);
			$printer->add_text_line(
				star_mb_str_pad($text, 15)
				. star_mb_str_pad($formatted_value, 10, " ", STR_PAD_LEFT)
				);
		};

		$printer->add_new_line(1);
		$printer->set_text_right_align();

		if($order->get_total_discount() > 0)
			$ft("DISCOUNT", -$order->get_discount_total());

		// Print any fees attached to the order
		$fees = $order->get_fees();
		foreach($fees as $fee) {
			$ft($fee->get_name(), $fee->get_total());
		}

		$taxes = $order->get_tax_totals();
		foreach ($taxes as $tax)
		{
			$ft("TAX ({$tax->label})", $tax->amount);
		}
		$ft("TAX TOTAL", $order->get_total_tax());

		$ft("TOTAL", $order->get_total());

		$printer->set_text_left_align();
	}

	// Print info below items list
	function star_cloudprnt_print_items_footer(&$printer, &$order)
	{
		$item_footer_message = get_option('star-cloudprnt-print-items-footer-message');

		if(empty($item_footer_message))
			return;

		$printer->add_new_line(1);
		$printer->add_word_wrapped_text_line($item_footer_message);
		
	}
	
	// Generate the Additional Order info section, which prints extra fields that are attached to the order
	// Such as delivery times etc.
	function star_cloudprnt_print_additional_order_info(&$printer, &$order)
	{
		if(get_option('star-cloudprnt-print-order-meta-cb') != "on")
			return;
		
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
				$printer->add_new_line(1);
				$printer->set_text_emphasized();
				$printer->add_text_line("Additional Order Information");
				$printer->cancel_text_emphasized();
			}

			$formatted_key = $item_data["key"];
			if(get_option('star-cloudprnt-print-order-meta-reformat-keys') == 'on')
				$formatted_key = mb_convert_case(mb_ereg_replace("_", " ", $formatted_key), MB_CASE_TITLE);

			$printer->add_word_wrapped_text_line(star_cloudprnt_filter_html($formatted_key) . ": " . star_cloudprnt_filter_html($item_data["value"]));
		}
		
	}

	// Print the address section - currently this prints the shipping address if present, otherwise
	// falls back to the billing address
	function star_cloudprnt_print_address(&$printer, &$order)
	{
		$order_meta = get_post_meta($order->get_id());

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
		$city = $gkv('_shipping_city');
		$state = $gkv('_shipping_state');
		$postcode = $gkv('_shipping_postcode');
		$tel = $gkv('_billing_phone');
		
		$printer->add_new_line(1);

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

	// Print info below items list
	function star_cloudprnt_print_customer_notes(&$printer, &$order)
	{
		$notes = star_cloudprnt_get_wc_order_notes($order->get_id());

		$printer->add_new_line(1);
		$printer->set_text_emphasized();
		$printer->add_text_line("Customer Provided Notes:");
		$printer->cancel_text_emphasized();
		
		if(empty($notes))
			$printer->add_text_line('None');
		else
			$printer->add_word_wrapped_text_line($notes);
	}

	// Generate the receipt header
	function star_cloudprnt_print_receipt_footer(&$printer, &$order)
	{
		// Print a bottom logo if configured
		if (get_option('star-cloudprnt-print-logo-bottom-input'))
			$printer->add_nv_logo(esc_attr(get_option('star-cloudprnt-print-logo-bottom-input')));
	}


	// Select which sections od the document to print and trigger rendering of each
	function star_cloudprnt_print_documentSections(&$printer, &$order)
	{
		// Default receipt sections
		$sections = array("header", "sub_header", "items_header", "items", "coupons", "item_totals", "items_footer", "order_info", "address", "notes", "footer", "end");
		$sections = apply_filters('smcpfw_sections', $sections, $order);
		
		foreach ($sections as $section)
		{
			// Check for any pre_section text/data to be printed
			$data = apply_filters('smcpfw_pre_' . $section, "", $order);

			// If pre-section data exists, then print it
			if(!empty($data))
			{
				$printer->clear_formatting();
				$rendered = apply_filters('smcpfw_render_pre_' . $section, $data, $printer, $order);
				if($rendered !== true)
					$printer->add_word_wrapped_text_line($data);
			}

			// Render the section body
			$printer->clear_formatting();
			$rendered = apply_filters('smcpfw_render_' . $section, false, $printer, $order);
			if($rendered !== true) {
				// Use internal rendering
				switch($section) {
					case "header":				star_cloudprnt_print_receipt_header($printer, $order);						break;
					case "sub_header":		star_cloudprnt_print_receipt_sub_header($printer, $order);				break;
					case "items_header":	star_cloudprnt_print_items_header($printer, $order);							break;
					case "items":					star_cloudprnt_print_items($printer, $order);											break;
					case "coupons":				star_cloudprnt_print_coupon_info($printer, $order);								break;
					case "item_totals":		star_cloudprnt_print_item_totals($printer, $order);								break;
					case "items_footer":	star_cloudprnt_print_items_footer($printer, $order);							break;
					case "order_info":		star_cloudprnt_print_additional_order_info($printer, $order);			break;
					case "address":				star_cloudprnt_print_address($printer, $order);										break;
					case "notes":					star_cloudprnt_print_customer_notes($printer, $order);						break;
					case "footer":				star_cloudprnt_print_receipt_footer($printer, $order);						break;
					case "end":						$printer->cut();																									break;
				}
			}

			// Check for any pre_section text/data to be printed
			$data = apply_filters('smcpfw_post_' . $section, "", $order);
			// If post-section data exists, then print it
			if(!empty($data))
			{
				$printer->clear_formatting();
				$rendered = apply_filters('smcpfw_render_post_' . $section, $data, $printer, $order);
				if($rendered !== true)
					$printer->add_word_wrapped_text_line($data);
			}

		}

	}


	function star_cloudprnt_print_order_summary($selectedPrinter, $file, $order_id)
	{
		$order = wc_get_order($order_id);

		// Get the correct object for building commands for the selected printer
		$printer = star_cloudprnt_command_generator($selectedPrinter, $file);
		
		// Ask the printer to use the correct text encoding
		$printer->set_codepage(get_option('star-cloudprnt-printer-encoding-select'));
		$printer->clear_formatting();

		// Sound a buzzer if it is connected - for 500ms
		if(get_option("star-cloudprnt-buzzer-start") == "on")
			$printer->sound_buzzer(1, 500, 100);

		// Allow any filters to render the whole receipt
		$ext_rendered = apply_filters('smcpfw_render_whole_receipt', false, $printer, $order);

		// If not rendered externally, then render by section.
		if($ext_rendered !== true) {
			star_cloudprnt_print_documentSections($printer, $order);
		}

		// Sound a buzzer if it is connected - for 500ms
		if(get_option("star-cloudprnt-buzzer-end") == "on")
			$printer->sound_buzzer(1, 500, 100);

		// Get the number of copies from the settings
		$copies=intval(get_option("star-cloudprnt-print-copies-input"));
		if($copies < 1) $copies = 1;
		
		// Send the print job to the spooling folder, ready to be connected by the printer and printed.
		$printer->printJob($copies);
	}


?>