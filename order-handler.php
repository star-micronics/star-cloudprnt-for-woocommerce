<?php
	function star_cloudprnt_get_column_separated_data($columns)
	{
		$max_chars = STAR_CLOUDPRNT_MAX_CHARACTERS_THREE_INCH;
		$total_columns = count($columns);
		
		if ($total_columns == 0) return "";
		if ($total_columns == 1) return $columns[0];
		if ($total_columns == 2)
		{
			$total_characters = strlen($columns[0])+strlen($columns[1]);
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
	
	function star_cloudprnt_get_seperator()
	{
		$max_chars = STAR_CLOUDPRNT_MAX_CHARACTERS_THREE_INCH;
		return str_repeat('_', $max_chars);
	}
	
	function star_cloudprnt_parse_order_status($status)
	{
		if ($status === 'wc-pending') return 'Pending Payment';
		else if ($status === 'wc-processing') return 'Processing';
		else if ($status === 'wc-on-hold') return 'On Hold';
		else if ($status === 'wc-completed') return 'Completed';
		else if ($status === 'wc-cancelled') return 'Cancelled';
		else if ($status === 'wc-refunded') return 'Refunded';
		else if ($status === 'wc-failed') return 'Failed';
		else return "Unknown";
	}
	
	function star_cloudprnt_get_codepage_1252_currency_symbol()
	{
		$symbol = get_woocommerce_currency_symbol();
		if ($symbol === "&pound;") return "\xA3"; // £ pound
		else if ($symbol === "&#36;") return "\x24"; // $ dollar
		else if ($symbol === "&euro;") return "\x80"; // € euro
		return ""; // return blank by default
	}
	
	function star_cloudprnt_get_formatted_variation($variation, $order, $item_id) 
	{
		$return = '';
		if (is_array($variation))
		{
			$variation_list = array();
			foreach ($variation as $name => $value)
			{
				// If the value is missing, get the value from the item
				if (!$value)
				{
					$meta_name = esc_attr(str_replace('attribute_', '', $name));
					$value = $order->get_item_meta($item_id, $meta_name, true);
				}

				// If this is a term slug, get the term's nice name
				if (taxonomy_exists(esc_attr(str_replace('attribute_', '', $name))))
				{
					$term = get_term_by('slug', $value, esc_attr(str_replace('attribute_', '', $name)));
					if (!is_wp_error($term) && ! empty($term->name))
					{
						$value = $term->name;
					}
				}
				else
				{
					$value = ucwords(str_replace( '-', ' ', $value ));
				}
				$variation_list[] = wc_attribute_label(str_replace('attribute_', '', $name)) . ': ' . rawurldecode($value);
			}
			$return .= implode('||', $variation_list);
		}
		return $return;
	}
	
	function star_cloudprnt_create_receipt_items($order, &$printer)
	{
		$order_items = $order->get_items();
		foreach ($order_items as $item_id => $item_data)
		{
			$product_name = $item_data['name'];
			$product_id = $item_data['product_id'];
			$variation_id = $item_data['variation_id'];
			
			$item_qty = $order->get_item_meta($item_id, "_qty", true);
			$item_total_price = floatval($order->get_item_meta($item_id, "_line_total", true))
							+floatval($order->get_item_meta($item_id, "_line_tax", true));
			$item_price = floatval($item_total_price) / intval($item_qty);
			$currencyHex = star_cloudprnt_get_codepage_1252_currency_symbol();
			$formatted_item_price = number_format($item_price, 2, '.', '');
			$formatted_total_price = number_format($item_total_price, 2, '.', '');
			
			$printer->set_text_emphasized();
			$printer->add_text_line(str_replace('&ndash;', '-', $product_name)." - ID: ".$product_id."");
			$printer->cancel_text_emphasized();
			
			if ($variation_id != 0)
			{
				$product_variation = new WC_Product_Variation( $variation_id );
				$variation_data = $product_variation->get_variation_attributes();
				$variation_detail = star_cloudprnt_get_formatted_variation($variation_data, $order, $item_id); 
				$exploded = explode("||", $variation_detail);
				foreach($exploded as $exploded_variation)
				{
					$printer->add_text_line(" ".ucwords($exploded_variation));
				}
			}
			$printer->add_text_line(star_cloudprnt_get_column_separated_data(array(" Qty: ".
						$item_qty." x Cost: ".$currencyHex.$formatted_item_price,
						$currencyHex.$formatted_total_price)));
		}
	}
	
	function star_cloudprnt_create_address($order, $order_meta, &$printer)
	{
		$fname = $order_meta[_shipping_first_name][0];
		$lname = $order_meta[_shipping_last_name][0];
		$a1 = $order_meta[_shipping_address_1][0];
		$a2 = $order_meta[_shipping_address_2][0];
		$city = $order_meta[_shipping_city][0];
		$state = $order_meta[_shipping_state][0];
		$postcode = $order_meta[_shipping_postcode][0];
		$tel = $order_meta[_billing_phone][0];
		
		$printer->set_text_emphasized();
		if ($a1 == '')
		{
			$printer->add_text_line("Billing Address:");
			$printer->cancel_text_emphasized();
			$fname = $order_meta[_billing_first_name][0];
			$lname = $order_meta[_billing_last_name][0];
			$a1 = $order_meta[_billing_address_1][0];
			$a2 = $order_meta[_billing_address_2][0];
			$city = $order_meta[_billing_city][0];
			$state = $order_meta[_billing_state][0];
			$postcode = $order_meta[_billing_postcode][0];
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
	
	// Checks order history and printer queue 
	function star_cloudprnt_is_duplicate_order($order_id)
	{
		$history_path = STAR_CLOUDPRNT_DATA_FOLDER_PATH.star_cloudprnt_get_os_path("/order_history.txt");
		$fh = fopen($history_path, "r");
		if ($fh)
		{
			while (($line = fgets($handle)) !== false)
			{
				// process the line read.
			}
			fclose($handle);
		}
		return false;
	}
	
	function star_cloudprnt_print_order_summary($selectedPrinter, $file, $order_id)
	{
		$order = wc_get_order($order_id);
		$shipping_items = @array_shift($order->get_items('shipping'));
		$order_meta = get_post_meta($order_id);
		
		$printer = new Star_CloudPRNT_Star_Line_Mode_Job($selectedPrinter, $file);
		$printer->set_codepage("20"); // 20 hex == 32 decimal == 1252 Windows Latin-1
		if (get_option('star-cloudprnt-print-logo-top-input')) $printer->add_nv_logo(esc_attr(get_option('star-cloudprnt-print-logo-top-input')));
		$printer->set_text_emphasized();
		$printer->set_text_center_align();
		$printer->add_text_line("ORDER NOTIFICATION");
		$printer->set_text_left_align();
		$printer->cancel_text_emphasized();
		$printer->add_new_line(1);
		$printer->add_text_line(star_cloudprnt_get_column_separated_data(array("Order #".$order_id, date("d-m-y H:i:s", time()))));
		$printer->add_new_line(1);
		$printer->add_text_line("Order Status: ".star_cloudprnt_parse_order_status($order->post->post_status));
		$printer->add_text_line("Order Date: ".$order->order_date);
		if (isset($shipping_items['name']))
		{
			$printer->add_new_line(1);
			$printer->add_text_line("Shipping Method: ".$shipping_items['name']);
		}
		$printer->add_text_line("Payment Method: ".$order_meta[_payment_method_title][0]);
		$printer->add_new_line(1);
		$printer->add_text_line(star_cloudprnt_get_column_separated_data(array('ITEM', 'TOTAL')));
		$printer->add_text_line(star_cloudprnt_get_seperator());
		
		star_cloudprnt_create_receipt_items($order, $printer);
		
		$printer->add_new_line(1);
		$printer->set_text_right_align();
		$formatted_overall_total_price = number_format($order_meta[_order_total][0], 2, '.', '');
		$printer->add_text_line("TOTAL     ".star_cloudprnt_get_codepage_1252_currency_symbol().$formatted_overall_total_price);
		$printer->set_text_left_align();
		$printer->add_new_line(1);
		$printer->add_text_line("All prices are inclusive of tax (if applicable).");
		$printer->add_new_line(1);
		
		star_cloudprnt_create_address($order, $order_meta, $printer);
		
		$printer->add_new_line(1);
		$printer->set_text_emphasized();
		$printer->add_text_line("Customer Provided Notes:");
		$printer->cancel_text_emphasized();
		$printer->add_text(empty($order->post->post_excerpt) ? "None" : $order->post->post_excerpt);
		if (get_option('star-cloudprnt-print-logo-bottom-input')) $printer->add_nv_logo(esc_attr(get_option('star-cloudprnt-print-logo-bottom-input')));
		
		$printer->printjob();
	}
	
	function star_cloudprnt_woo_on_thankyou($order_id)
	{
		$file = STAR_CLOUDPRNT_PRINTER_PENDING_SAVE_PATH.star_cloudprnt_get_os_path("/order_".$order_id."_".time().".bin");
		
		$selectedPrinter = "";
		$printerList = star_cloudprnt_get_printer_list();
		if (!empty($printerList))
		{
			foreach ($printerList as $printer)
			{
				if (get_option('star-cloudprnt-printer-select') == $printer['name'])
				{
					$selectedPrinter = $printer['printerMAC'];
					break;
				}
			}
			
			if ($selectedPrinter === "" && count($printerList) === 1) $selectedPrinter = $printer['printerMAC'];
			
			if ($selectedPrinter !== "") star_cloudprnt_print_order_summary($selectedPrinter, $file, $order_id);
		}
	}
	
	function star_cloudprnt_setup_order_handler()
	{
		if (selected(get_option('star-cloudprnt-select'), "enable", false) !== "" && star_cloudprnt_is_woo_activated())
		{
			add_action('woocommerce_thankyou', 'star_cloudprnt_woo_on_thankyou', 1, 1);
		}
	}
?>