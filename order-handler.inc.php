<?php
  /*
   * Support functions for order-handler.php, anything not related to actually rendering a print job is here
   *
   */

  mb_internal_encoding('utf-8');
	mb_regex_encoding("UTF-8");
	
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


  // Pad strings with spaces so that they column align across the indicated $max_chars characters
  // Note: recommended for 2 column layout only, since column padding is recalculated per line.
  // Note1: This uses mb_strwidth to attampt to handle both full and half width characters, but
  //        results are not always consistent, so printing full-width (Japanese, Chinese etc.)
  //        is probably better done without column layouts for now - to be investigated.
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
  
  // Retrieve order notes text
  function star_cloudprnt_get_wc_order_notes($order_id){
		//make sure it's a number
		$order_id = intval($order_id);
		//get the post 
		$post = get_post($order_id);
		//if there's no post, return as error
		if (!$post) return false;

		return $post->post_excerpt;
	}

  // Return the site currency symbol, converted to the printers target encoding
	function star_cloudprnt_get_codepage_currency_symbol()
	{
		$encoding = get_option('star-cloudprnt-printer-encoding-select');
		$symbol = get_woocommerce_currency_symbol();

		return star_cloudprnt_filter_html($symbol);
	}
	
	// Format a float value as a currency string
	function star_cloudprnt_format_currency($value)
	{
		return star_cloudprnt_get_codepage_currency_symbol() . number_format($value, 2, '.', '');
	}

  // Convert html data to printer ready text. Note that this preserves no formatting, all tags are stripped
  // and newlines removed, it is intended for printing field names/values.
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

  // Get printer compatibility information and generate a print job
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
      
      // If no printer has been selected, then choose the first one on the list
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
						jQuery("#star_cp_job_sent").show();
						setTimeout(() => {
							jQuery("#star_cp_job_sent").hide();
						}, 2000);
					});
				}

			</script>

			<a href="javascript: star_cloudprnt_trigger();"><span><span class="dashicons dashicons-printer"></span> Print with Star CloudPRNT</span></a>
      <span id="star_cp_job_sent" style="display:none"><span class="dashicons dashicons-yes"></span></span>

		<?php
	}

	// Handle the ajax reprint action
	function star_cloudprnt_reprint_button_callback()
	{
		star_cloudprnt_trigger_print($_POST["order_id"]);
		wp_die();
	}

  // Register necessary actions for triggering an order print
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
			} elseif ($trigger == "status_on-hold") {
				add_action('woocommerce_order_status_on-hold', 'star_cloudprnt_trigger_print', 1, 1);
			}

		}
	}

?>