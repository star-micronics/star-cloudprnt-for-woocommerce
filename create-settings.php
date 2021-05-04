<?php
	include_once('printer-settings-page.php');

	function star_cloudprnt_is_woo_activated()
	{
		return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
	}

	function star_cloudprnt_settings()
	{

		add_settings_section("star_cloudprnt_setup_section", __("CloudPRNT Setup", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_setup_section_info", "star_cloudprnt_setup");
		add_settings_field("star-cloudprnt-select", __("CloudPRNT", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");
		add_settings_field("star-cloudprnt-printer-select", __("Selected Printer", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_printer_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");

		add_settings_field("star-cloudprnt-printer-encoding-select", __("Text Encoding", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_printer_encoding_select_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");

		add_settings_field("star-cloudprnt-trigger", __("Printing Trigger", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_trigger_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");

		add_settings_field("star-cloudprnt-print-copies-input", __("Copies", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_print_copies_input_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");

		add_settings_field("star-cloudprnt-buzzer", __("Buzzer", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_buzzer_display", "star_cloudprnt_setup", "star_cloudprnt_setup_section");

		add_settings_section("star_cloudprnt_design_section", __("Print Job Design Options", "star-cloudprnt-for-woocommerce"), "star_cloudprnt_design_header", "star_cloudprnt_setup");
		add_settings_field("star-cloudprnt-header", __("Header", "star-cloudprnt-for-woocommerce"), "star_cloudPRNT_header_settings_display", "star_cloudprnt_setup", "star_cloudprnt_design_section");
		add_settings_field("star-cloudprnt-items", __("Item List", "star-cloudprnt-for-woocommerce"), "star_cloudPRNT_item_settings_display", "star_cloudprnt_setup", "star_cloudprnt_design_section");
		add_settings_field("star-cloudprnt-order-fields", __("Additional Order Fields", "star-cloudprnt-for-woocommerce"), "star_cloudPRNT_order_fields_display", "star_cloudprnt_setup", "star_cloudprnt_design_section");
		add_settings_field("star-cloudprnt-logo", __("Logo", "star-cloudprnt-for-woocommerce"), "star_cloudPRNT_print_logo_display", "star_cloudprnt_setup", "star_cloudprnt_design_section");

	}

	function star_cloudprnt_register_settings()
	{
		/* Attempt to set the default print job trigger to "status_processing" for new installs, but "thankyou" for sites that have
		   already been running with the plugin - to avoid potentially breaking thos sites after upgrading the plugin, since some sites use
			 other plugins to change the default order status.
			 */
			$trigger_default = "status_processing";						// Recommended default for new sites, at which time users can choose another option if preferred
			if(get_option('star-cloudprnt-select') != false)
				$trigger_default = "thankyou";

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-select");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-printer-select");

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-printer-encoding-select");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-copies-input");

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-trigger", array("default" => $trigger_default));

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-header-title", array("default" => __("ORDER NOTIFICATION", 'star-cloudprnt-for-woocommerce')));

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-items-print-id", array("default" => "on"));
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-items-print-sku", array("default" => "off"));

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-items-footer-message", array("default" => __("All prices are inclusive of tax (if applicable).", 'star-cloudprnt-for-woocommerce')));

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-order-meta-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-order-meta-reformat-keys", array("default" => "on"));
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-order-meta-hidden", array("default" => "off"));
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-order-meta-exclusions", array("default" => __("is_vat_exempt, mailchimp_woocommerce_campaign_id, mailchimp_woocommerce_landing_site", 'star-cloudprnt-for-woocommerce')));

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-top-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-top-input");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-bottom-cb");
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-print-logo-bottom-input");

		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-buzzer-start", array("default" => ""));
		register_setting("star_cloudprnt_setup_section", "star-cloudprnt-buzzer-end", array("default" => ""));
	}


	function star_cloudprnt_menu_item()
	{
		add_submenu_page("options-general.php", __("Star CloudPRNT for WooCommerce", 'star-cloudprnt-for-woocommerce'), "Star CloudPRNT for WooCommerce", "manage_options", "star-cloudprnt-settings-admin", "star_cloudprnt_page");
	}

	function star_cloudprnt_setup_section_info()
	{
		printf( '<strong>%s</strong><br>', esc_html__('Set your printer "Server URL" to:', 'star-cloudprnt-for-woocommerce') );
		print plugins_url('cloudprnt/cloudprnt.php', __FILE__);
	}

	function star_cloudprnt_select_display()
	{
	   ?>
			<select name="star-cloudprnt-select">
				<option value="disable" <?php selected(get_option('star-cloudprnt-select'), "disable"); ?>><?php esc_html_e('DISABLE', 'star-cloudprnt-for-woocommerce');?></option>
				<option value="enable" <?php selected(get_option('star-cloudprnt-select'), "enable"); ?>><?php esc_html_e('ENABLE', 'star-cloudprnt-for-woocommerce');?></option>
			</select>
	   <?php
	}


	function star_cloudprnt_printer_encoding_select_display()
	{
	   ?>
			<select name="star-cloudprnt-printer-encoding-select">
                <option value="UTF-8" <?php selected(get_option('star-cloudprnt-printer-encoding-select'), "utf-8"); ?>><?php esc_html_e('UTF-8', 'star-cloudprnt-for-woocommerce');?></option>
                <option value="1252" <?php selected(get_option('star-cloudprnt-printer-encoding-select'), "1252"); ?>><?php esc_html_e('1252', 'star-cloudprnt-for-woocommerce');?></option>
			</select>
			<label><?php esc_html_e('UTF-8 mode is recommended for mC-Print or TSP650II printer models.', 'star-cloudprnt-for-woocommerce');?></label>
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
		if (empty($printerList)) echo '<select name="star-cloudprnt-printer-select" disabled><option value="none">'.esc_html__('No printer found', 'star-cloudprnt-for-woocommerce').'</option></select>';
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

			echo '<a href="javascript: void(0);" onclick="star_cloudprnt_load_printer_settings()" style="margin-left: 10px">'.esc_html__('Edit', 'star-cloudprnt-for-woocommerce').'</a>';

		}
	}

	function star_cloudprnt_trigger_display()
	{
		?>
			<input type="radio" name="star-cloudprnt-trigger" value="status_processing" <?php checked(get_option('star-cloudprnt-trigger'), 'status_processing', true) ?>>
			<label><?php esc_html_e('When an order is assigned the "processing" status (recommended for most sites)', 'star-cloudprnt-for-woocommerce');?></label><br>
			<input type="radio" name="star-cloudprnt-trigger" value="status_completed" <?php checked(get_option('star-cloudprnt-trigger'), 'status_completed', true) ?>>
			<label><?php esc_html_e('When an order is assigned the "completed" status', 'star-cloudprnt-for-woocommerce');?></label><br>
			<input type="radio" name="star-cloudprnt-trigger" value="status_on-hold" <?php checked(get_option('star-cloudprnt-trigger'), 'status_on-hold', true) ?>>
			<label><?php esc_html_e('When an order is assigned the "on hold" status', 'star-cloudprnt-for-woocommerce');?></label><br>
			<input type="radio" name="star-cloudprnt-trigger" value="thankyou" <?php checked(get_option('star-cloudprnt-trigger'), 'thankyou', true) ?>>
			<label><?php esc_html_e('When WooCommerce "Thank You" message is displayed (<span class="star_cp_caution">&#x26a0;</span> legacy option, not recommended)', 'star-cloudprnt-for-woocommerce');?></label><br>
			<input type="radio" name="star-cloudprnt-trigger" value="none" <?php checked(get_option('star-cloudprnt-trigger'), 'none', true) ?>>
			<label><?php esc_html_e('Disable automatic printing', 'star-cloudprnt-for-woocommerce');?></label><br>
		<?php
	}


	function star_cloudprnt_design_header()
	{
		?>
		<?php
	}

	function star_cloudPRNT_header_settings_display()
	{
		?>
			<label><?php esc_html_e('Receipt Title', 'star-cloudprnt-for-woocommerce');?></label><br/>
			<input type="text" name="star-cloudprnt-print-header-title" size=60 value="<?php echo get_option('star-cloudprnt-print-header-title') ?>">
		<?php
	}

	function star_cloudPRNT_item_settings_display()
	{
		?>
			<input type="checkbox" name="star-cloudprnt-print-items-print-id" value="on" <?php checked(get_option('star-cloudprnt-print-items-print-id'), 'on', true) ?> >
			<label><?php esc_html_e('Include Item ID', 'star-cloudprnt-for-woocommerce');?></label><br/>

			<input type="checkbox" name="star-cloudprnt-print-items-print-sku" value="on" <?php checked(get_option('star-cloudprnt-print-items-print-sku'), 'on', true) ?> >
			<label><?php esc_html_e('Include Item SKU', 'star-cloudprnt-for-woocommerce');?></label><br/>

			<br/>
			<label><?php esc_html_e('Item list footer message', 'star-cloudprnt-for-woocommerce');?></label><br/>
			<textarea type="text" name="star-cloudprnt-print-items-footer-message" cols=60 rows=4><?php echo get_option('star-cloudprnt-print-items-footer-message') ?></textarea>
		<?php
	}


	function star_cloudPRNT_order_fields_display()
	{
		?>
			<input type="checkbox" name="star-cloudprnt-print-order-meta-cb" value="on" <?php checked(get_option('star-cloudprnt-print-order-meta-cb'), 'on', true) ?> >
			<label><?php esc_html_e('Print additional order meta-data, such as custom fields.', 'star-cloudprnt-for-woocommerce');?></label>
			<div style="padding-left: 7mm">
				<input type="checkbox" name="star-cloudprnt-print-order-meta-reformat-keys" value="on" <?php checked(get_option('star-cloudprnt-print-order-meta-reformat-keys'), 'on', true) ?> >
				<label><?php esc_html_e('Re-format key names (e.g. print "delivery_time" as "Delivery Time")', 'star-cloudprnt-for-woocommerce');?></label><br/>
				<input type="checkbox" name="star-cloudprnt-print-order-meta-hidden" value="on" <?php checked(get_option('star-cloudprnt-print-order-meta-hidden'), 'on', true) ?> >
				<label><?php esc_html_e('Include hidden fields', 'star-cloudprnt-for-woocommerce');?></label><br/>
				<br/><label><?php esc_html_e('Exclude items with these key names (',' separated)', 'star-cloudprnt-for-woocommerce');?></label><br/>
				<input type="text" name="star-cloudprnt-print-order-meta-exclusions" size=60 value="<?php echo get_option('star-cloudprnt-print-order-meta-exclusions') ?>">

			</div>
		<?php
	}

	function star_cloudPRNT_print_logo_display()
	{
		$top_option_value = '01';
		$top_disabled = (get_option('star-cloudprnt-print-logo-top-cb') === 'on') ? "" : " disabled";
		if (get_option('star-cloudprnt-print-logo-top-input')) $top_option_value = esc_attr(get_option('star-cloudprnt-print-logo-top-input'));

		$end_option_value = '01';
		$end_disabled = (get_option('star-cloudprnt-print-logo-bottom-cb') === 'on') ? "" : " disabled";
		if (get_option('star-cloudprnt-print-logo-bottom-input')) $end_option_value = esc_attr(get_option('star-cloudprnt-print-logo-bottom-input'));

		?>
			<!--<label>Top of page:</label> -->
			<input type="checkbox" name="star-cloudprnt-print-logo-top-cb" <?= checked(get_option('star-cloudprnt-print-logo-top-cb'), 'on', false); ?> onclick='document.getElementById("star-cloudprnt-top-logo-input").disabled = !this.checked;'>
			<label><?php esc_html_e('Print at top of page',' separated)', 'star-cloudprnt-for-woocommerce');?></label>
			<input type="text" style="width: 15mm;" id="star-cloudprnt-top-logo-input" name="star-cloudprnt-print-logo-top-input" value="<?= $top_option_value ?>" <?= $top_disabled ?> >
			<label><?php esc_html_e('Keycode',' separated)', 'star-cloudprnt-for-woocommerce');?></label><br/>

			<!--<label>End of page:</label> -->
			<input type="checkbox" name="star-cloudprnt-print-logo-bottom-cb" <?= checked(get_option('star-cloudprnt-print-logo-bottom-cb'), 'on', false); ?> onclick='document.getElementById("star-cloudprnt-bottom-logo-input").disabled = !this.checked;'>
			<label><?php esc_html_e('Print at end of page',' separated)', 'star-cloudprnt-for-woocommerce');?></label>
			<input type="text" style="width: 15mm;" id="star-cloudprnt-bottom-logo-input" name="star-cloudprnt-print-logo-bottom-input" value="<?= $end_option_value ?>" <?= $end_disabled ?> >
			<label><?php esc_html_e('Keycode',' separated)', 'star-cloudprnt-for-woocommerce');?></label><br/>
			<p><?php sprintf('<strong>%s</strong>%s<a href="http://starmicronics.com/support/Default.aspx">%s</a>',
                    esc_html__('Note:', 'star-cloudprnt-for-woocommerce'),
                    esc_html__('logos should be written to printer FlashROM memory, using a suitable tool, such as the StarPRNT Software for Windows, which can be downloaded from the', 'star-cloudprnt-for-woocommerce'),
                    esc_html__('Star global downlad site', 'star-cloudprnt-for-woocommerce')); ?></p>

		<?php
	}

	function star_cloudprnt_buzzer_display()
	{
		?>
			<input type="checkbox" name="star-cloudprnt-buzzer-start" value="on" <?php checked(get_option('star-cloudprnt-buzzer-start'), 'on', true) ?> >
			<label><?php esc_html_e('Sound  external buzzer before printing',' separated)', 'star-cloudprnt-for-woocommerce');?></label><br/>
			<input type="checkbox" name="star-cloudprnt-buzzer-end" value="on" <?php checked(get_option('star-cloudprnt-buzzer-end'), 'on', true) ?> >
			<label><?php esc_html_e('Sound  external buzzer after printing',' separated)', 'star-cloudprnt-for-woocommerce');?></label><br/>
		<?php
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
		?>
			<style>
				.star_cp_caution {
					color: orange;
					font-size: larger;
					font-weight: bolder;
				},
			</style>
		<?php

		$plugin_data = get_plugin_data(plugin_dir_path(__FILE__).star_cloudprnt_get_os_path('star-cloudprnt-for-woocommerce.php'));

		echo '<div class="wrap">';
			echo '<img src="'.plugins_url('images/logo.png', __FILE__).'">';
				echo sprintf( '<h1>%s</h1>', esc_html__('Star CloudPRNT for WooCommerce Settings', 'star-cloudprnt-for-woocommerce') );
				echo '<h2>Version ' . $plugin_data['Version'] . '</h2>';

				if (!star_cloudprnt_is_woo_activated())
				{
					echo '<br><span style="color: red"><span class="dashicons dashicons-no"></span>' . esc_html__('Warning: Unable to detect WooCommerce plugin.', 'star-cloudprnt-for-woocommerce') . '<br/>' . esc_html__('This can sometimes occur, if the plugin has been installed to a custom folder. If you are certain that WooCommerce is installed and functioning, then you can safely ignore this warning', 'star-cloudprnt-for-woocommerce') . '</span>';
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
