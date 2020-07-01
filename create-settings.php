<?php
	include_once('printer-settings-page.php');

	function star_cloudprnt_is_woo_activated()
	{
		return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
	}

	function star_cloudprnt_settings()
	{
		add_settings_section("star_cloudprnt_setup_section", "CloudPRNT Setup", "star_cloudprnt_setup_section_info", "star_cloudprnt_setup");
		add_settings_field("star-cloudprnt-select", "CloudPRNT", "star_cloudprnt_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");  
		add_settings_field("star-cloudprnt-printer-select", "Selected Printer", "star_cloudprnt_printer_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section"); 
		
		add_settings_field("star-cloudprnt-printer-encoding-select", "Text Encoding", "star_cloudprnt_printer_encoding_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");
		
		add_settings_field("star-cloudprnt-print-order-meta-cb", "Additional Order Fields", "star_cloudprnt_print_order_meta_cb_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");  
		
		add_settings_field("star-cloudprnt-print-copies-input", "Copies", "star_cloudprnt_print_copies_input_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");  
		
		add_settings_section("star_cloudprnt_print_logo_settings_section", "Printer Logo Settings", "star_cloudprnt_printer_logo_settings_header", "star_cloudprnt_setup");
		add_settings_field("star-cloudprnt-print-logo-top-cb", "Print Logo (Top of Receipt)", 
			"star_cloudprnt_print_logo_top_display", "star_cloudprnt_setup", "star_cloudprnt_print_logo_settings_section");  		
		add_settings_field("star-cloudprnt-print-logo-top-input", "Top Logo Key Code", 
			"star_cloudprnt_print_logo_top_input_display", "star_cloudprnt_setup", "star_cloudprnt_print_logo_settings_section");  		
		add_settings_field("star-cloudprnt-print-logo-bottom-cb", "Print Logo (Bottom of Receipt)", 
			"star_cloudprnt_print_logo_bottom_display", "star_cloudprnt_setup", "star_cloudprnt_print_logo_settings_section");  		
		add_settings_field("star-cloudprnt-print-logo-bottom-input", "Bottom Logo Key Code", 
			"star_cloudprnt_print_logo_bottom_input_display", "star_cloudprnt_setup", "star_cloudprnt_print_logo_settings_section"); 
		
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-select");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-printer-select");
		
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-printer-encoding-select");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-order-meta-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-copies-input");
		
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-top-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-top-input");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-bottom-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-bottom-input");
	}
	
	function star_cloudprnt_menu_item()
	{
		add_submenu_page("options-general.php", "Star CloudPRNT for WooCommerce", "Star CloudPRNT for WooCommerce", "manage_options", "star-cloudprnt-settings-admin", "star_cloudprnt_page"); 
	}
	
	function star_cloudprnt_setup_section_info()
	{
		print '<strong>Set your printer "Server URL" to:</strong><br>';
		print plugins_url('cloudprnt/cloudprnt.php', __FILE__);
	}

	function star_cloudprnt_select_display()
	{
	   ?>
		<select name="star-cloudprnt-select">
			<option value="disable" <?php selected(get_option('star-cloudprnt-select'), "disable"); ?>>DISABLE</option>
			<option value="enable" <?php selected(get_option('star-cloudprnt-select'), "enable"); ?>>ENABLE</option>
		</select>
	   <?php
	}
	
	function star_cloudprnt_printer_logo_settings_header()
	{
		?>
		<p>Logos should be writtent to printer FlashROM memory, using a suitable tool, such as the
		   StarPRNT Software for Windows, which can be downloaded from the <a href="http://starmicronics.com/support/Default.aspx">Star global downlad site</a>.
		<?php
	}
	
	function star_cloudprnt_printer_encoding_select_display()
	{
	   ?>
		<select name="star-cloudprnt-printer-encoding-select">
			<option value="UTF-8" <?php selected(get_option('star-cloudprnt-printer-encoding-select'), "utf-8"); ?>>UTF-8</option>
			<option value="1252" <?php selected(get_option('star-cloudprnt-printer-encoding-select'), "1252"); ?>>1252</option>
		</select>
		<label>UTF-8 mode is recommended for mC-Print or TSP650II printer models.</label>
	   <?php
	}

	function star_cloudprnt_print_order_meta_cb_display()
	{
		?>
			<!-- <input type='hidden' value='off' name='star-cloudprnt-print-order-meta-cb'>-->
			<input type="checkbox" name="star-cloudprnt-print-order-meta-cb" value="on" <?php checked(get_option('star-cloudprnt-print-order-meta-cb'), 'on', true) ?> >
			<label>Print additional order meta-data, such as custom fields.</label>
		<?php

	}
	
	function star_cloudprnt_print_copies_input_display()
	{
		$copies=get_option("star-cloudprnt-print-copies-input");
		$copies = intval($copies);
		if($copies < 1) $copies = 1;
		
		?>
			<input type="number" name="star-cloudprnt-print-copies-input" value="<?php echo $copies; ?>" min=1 max=10>
		<?php
	}

	function star_cloudprnt_printer_select_display()
	{
		$printerList = star_cloudprnt_get_printer_list();
		if (empty($printerList)) echo '<select name="star-cloudprnt-printer-select" disabled><option value="none">No printer found</option></select>';
		else
		{
			$selectedPrinter = "";
			echo '<select id="star-cloudprnt-printer-select-id" name="star-cloudprnt-printer-select">';
			foreach ($printerList as $printer)
			{
				?>
				<script type="text/javascript">
					function star_cloudprnt_load_printer_settings()
					{
						var selected_printer_cb = document.getElementById("star-cloudprnt-printer-select-id");
						var selected_printer = selected_printer_cb.options[selected_printer_cb.selectedIndex].value;
						window.location.href = '?page=<?php echo $_GET['page']; ?>&printersettings='+btoa(selected_printer);
					}
				</script>
				<option value="<?php echo $printer['name']; ?>" <?php selected(get_option('star-cloudprnt-printer-select'), $printer['name']); ?>><?php echo $printer['name']; ?></option>
				<?php
				if (get_option('star-cloudprnt-printer-select') == $printer['name']) $selectedPrinter = $printer['printerMAC'];
			}
			echo '</select>';
			
			echo '<a href="javascript: void(0);" onclick="star_cloudprnt_load_printer_settings()" style="margin-left: 10px">Edit</a>';
		}
	}
	
	function star_cloudprnt_print_logo_top_display()
	{
		echo '<input type="checkbox" name="star-cloudprnt-print-logo-top-cb" '.checked(get_option('star-cloudprnt-print-logo-top-cb'), 'on', false).' onclick="document.getElementById(\'star-cloudprnt-top-logo-input\').disabled = !this.checked;">';
	}
	

	function star_cloudprnt_print_logo_top_input_display()
	{
		$option_value = '01';
		if (get_option('star-cloudprnt-print-logo-top-input')) $option_value = esc_attr(get_option('star-cloudprnt-print-logo-top-input'));
		$disabled = (get_option('star-cloudprnt-print-logo-top-cb') === 'on') ? "" : " disabled";
		echo '<input type="text" style="width: 30px;" id="star-cloudprnt-top-logo-input" name="star-cloudprnt-print-logo-top-input" value="'.$option_value.'"'.$disabled.'>';
	}
	
	function star_cloudprnt_print_logo_bottom_display()
	{
		echo '<input type="checkbox" name="star-cloudprnt-print-logo-bottom-cb" '.checked(get_option('star-cloudprnt-print-logo-bottom-cb'), 'on', false).' onclick="document.getElementById(\'star-cloudprnt-bottom-logo-input\').disabled = !this.checked;">';
	}
	
	function star_cloudprnt_print_logo_bottom_input_display()
	{
		$option_value = '01';
		if (get_option('star-cloudprnt-print-logo-bottom-input')) $option_value = esc_attr(get_option('star-cloudprnt-print-logo-bottom-input'));
		$disabled = (get_option('star-cloudprnt-print-logo-bottom-cb') === 'on') ? "" : " disabled";
		echo '<input type="text" style="width: 30px;" id="star-cloudprnt-bottom-logo-input" name="star-cloudprnt-print-logo-bottom-input" value="'.$option_value.'"'.$disabled.'>';
	}
	
	function star_cloudprnt_show_settings_page()
	{
		echo '<form method="post" action="options.php">';
		settings_fields("star_cloudprnt_setup_section");
		do_settings_sections("star_cloudprnt_setup");
		submit_button();
		echo '</form>';
	}

	function star_cloudprnt_page()
	{
		echo '<div class="wrap">';
			echo '<img src="'.plugins_url('images/logo.png', __FILE__).'">';
				echo '<h1>Star CloudPRNT for WooCommerce Settings</h1>';

				if (!star_cloudprnt_is_woo_activated())
				{
					echo '<br><span style="color: red"><span class="dashicons dashicons-no"></span>Warning: Unable to detect WooCommerce plugin.<br/>This can sometimes occur, if the plugin has been installed to a custom folder. If you are certain that WooCommerce is installed and functioning, then you can safely ignore this warning</span>';
				}
		
				if (isset($_GET['printersettings'])) 
				{
					if (isset($_GET['npn'])) star_cloudprnt_change_printer_name();
					else if (isset($_GET['cq'])) star_cloudprnt_clear_printer_queue();
					else if (isset($_GET['coh'])) star_cloudprnt_clear_order_history();
					else if (isset($_GET['dp'])) star_cloudprnt_delete_printer();
					else star_cloudprnt_show_printer_settings_page();
				}
				else star_cloudprnt_show_settings_page();
					
					
		echo '</div>';
	}
	
	function star_cloudprnt_create_settings_page()
	{
		add_action("admin_init", "star_cloudprnt_settings");
		add_action("admin_menu", "star_cloudprnt_menu_item");
	}
?>