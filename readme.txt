=== Star CloudPRNT for WooCommerce ===
Contributors: lawrenceowen, athompson1, gcubero, fmahmood
Tags: star, printing, printers, automated, e-commerce, store, sales, downloadable, downloads, woocommerce, restaurant, order, receipt
Requires at least: 5.0
Tested up to: 5.6
Requires PHP: 7.2
Stable tag: 2.0.3
License: MIT

Star CloudPRNT for WooCommerce enables Cloud printing technology with your Star Receipt printer.

== Description ==
Star CloudPRNT for WooCommerce will enable you to print automated receipts each time a customer places an order on your WooCommerce based eCommerce website.
This can be used to quickly create a remote order/receipt printing solution for restaurants, take-aways, bakeries and similar sites, with minimal set-up time or cost.

This plugin can only be used alongside the WooCommerce WordPress plugin.  For more information on the WooCommerce plugin please visit www.woocommerce.com

== Installation ==
1. Install and activate the plugin.
2. Go to your WordPress admin control panel and you should notice a new link for "Star CloudPRNT for WooCommerce" under your WordPress "Settings" category.
3. Once you have opened up the Star CloudPRNT settings page, you will be given a unique Server URL which you need to copy.
4. Next setup your compatible Star printer onto your network and run a self-test on the printer to get its IP address.
5. Type the printer IP address into a web browser to access its web interface and then login (default username "root" and password "public").
6. Navigate to the "Star CloudPRNT" section and enable the CloudPRNT service, then paste the URL you copied earlier into the "Server URL" field.
7. Press "Submit" then navigate to the "Save" section.
8. Choose "Save --> Configuration printing --> Restart device" and the printer will reboot (this can take up to 2 minutes).
9. Once the printer has rebooted, go back to your Star CloudPRNT settings page and refresh it, you will notice your printer will be populated in the printer list which means your printer is now setup and ready to use.
10. Finally, you can now enable the CloudPRNT service within the Star CloudPRNT settings page, once enabled all orders placed through WooCommerce will also be printed out on your Star printer.

For help and support please e-mail support@star-emea.com

= Video Installation Guide =
https://www.youtube.com/watch?v=2O3pZJ-kfqk

= Minimum Requirements =
* PHP 7.2 or greater.
* WordPress 5.0, 5.1, 5.3, 5.4, 5.5, 5.6, 5.7
* WooCommerce plugin 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0, 5.1
* Star TSP650II, TSP700II, TSP800II or SP700 series printer with a IFBD-HI01X/HI02X interface. Printer interface firmware 1.6 or later recommended.
* Star mC-Print3 or mC-Print2 series printer, firmware version 3.0 or later recommended.


== Frequently Asked Questions ==
 
= Which printer models are supported? =
 
Supported printer models are:

* Star TSP650II with HIX interface
* Star mC-Print3 (required plugin version 1.1.0 or later)
* Star mC-Print2 (required plugin version 1.1.0 or later)
* Star TSP700II with HIX interface
* Star TSP800II with HIX interface (currently limited to 80mm paper width support)
* Star SP700 with HIX interface

Version 3.0 firmware or later is recommended for mC-Print printer models.

= Can I print my local language characters? =

Yes, you can enable UTF-8 text encoding if you have a compatible printer model to resolve typical language specific character issues.
Please use the plugin settings page to enable UTB-8 mode.

Models with UTF-8 support are the TSP650II, mC-Print3 and mC-Print2. 
These printers cover most Latin, Cyrilic, Greek and CJK characters, although not all characters can be supported by all device models and for TSP654II a firmware update may be required. Please talk with Star for advice if necessary.
Support for full-width CJK characters is partial. They are supported but there may be page formatting and word wrapping errors.

There is currently no support for printing right-to-left languages in this plugin. If this is required, please talkw with Star about alternative integration options.

= Can I modify the print job design/layout? =

At this time, modifying the print job layout is possible only by directly editing the plugin PHP source code. This is entirely permitted by the license terms.
Your local Star Micronics support contact may be able to offer some assistance.

= How can I add an image to the top or end of my print? =

Images must be pre-stored inside the printer FlashROM memory in order to be printed bu this plugin. Once stored, they will have logo number/id, which can be input into the "Printer Logo Settings" settings area to enable top and/or bottom logo printing.
Storing images inside the Print FlashROM is possible with a software utility provided by Star Micronics, included with the standard indows driver package. These can be downloaded from the Star Micronics web site.

= Sometimes orders do not print, or print later than expected =

Please make sure that you update your plugin version to 2.0.0 or later, and ensure that your printing trigger is not set to "Thank You", this method of detecting new orders is not considered to be reliable, and included only for compatibility with existing sites using older versions of the plugin.
If issues continue, then please check the quality of your printers internet connection so that it can communicatw with your site. Star printers have a disconection message print option that can sometimes help to diagnose network connectivity issues. Please talk with your local Star Micronics support office if assistance is needed.

= My printer does not appear on the "Selected Printer" dropdown list =

This means that you printer is unable to connect with your server, and can have many potential causes:

  As a first step, if you are using an mC-Print2 or mC-Print3 model printer, the please update your printer firmware to version 3.0 or later to ensure compatibility with web hosts that have stronger TLS encryption.
  Firmware can be updated using the Star "mC-Print Utility" app, available via Google Play, or Apple App stores.

  Check that the printer has a working outgoing internet connection. Star tech support may be able to help with this if needed.

  Ensure that your your hosting does not have a traffic filter that may be blocking the printer from connecting to your web site or aggressively caching responses to your sites CloudPRNT URL. It may be necessary to ask your hosting provided to set up a rule to allow your printer to connect to your sites CloudPRNT URL. They must allow at least GET and POST requests without caching responses.


== Screenshots ==
1. Star CloudPRNT settings page.
2. Printer management page.

== Upgrade Notice ==

= 2.0.0 =
* Make printing trigger method user configurable - can improve reliability
* Support order and item metadata/custom field printing (Add-ons, delivery times etc.)
* Extended receipt configuration
* Print button on the edit order page

= 1.1.0 = 
* Add Support for Star mC-Print3 and mC-Print2 printer ranges

== Changelog ==

= 2.0.3 =
* Improve buzzer compatibility with mC-Print models.
* Add option to print ID and or SKU on item lines
* Add option to trigger printing when and order is assigned on-hold status
* Enable word-wrapping when printing items, in case of very long item names
* Enable Word-wrapping when printing header section fields, in case of long information on 58mm/2" paper
* Format the sub-heading order number and print timestamp on tow lines, in case they can not fit side by side
* Add printer control class methods for text highlighting, Qr Code, and Barcode printing methods to the printer command generation classes.
  Not currently exposed as features but may be useful to anyone forking/modifying the plugin.

= 2.0.2 =
* Fix missing "Shipping Method" from the receipt header.

= 2.0.1 =
* Do not print any blank lines when the title, or item footer text is empty
* Add some mailchimp metadata key names to the defailt exclusions list
* Change the timing for registering settings, to avoid reported issue with accessing settings with their default value.
* Remove all settings when uninstalled

= 2.0.0 =
* Make printing trigger method user configurable
* Clean-up settings page design
* Add a setting to list custom fields that should be excluded from printing
* Add a "Print with Star CloudPRNT" button to the Edit Order page sidebar
* Add a settings link to the plugins WordPress plugins page
* Add support for printing order item metadata - compatible with many Add-Ons plugins
* Add support for printing order metadata - compatible with many delivery scheduling plugins and others that append fields to an order
* Add a setting to support multiple copies
* Add support for setting an alternative print name for products by adding a "star_cp_print_name" attribute
* Display a warning when WooCommerce can not be detected, instead of refusing to work completely, because the detection can fail in case of some custom WooCommerce installs.
* Improve Timestamp display to match the site formatting local and site timezone - instead of server timezone
* Refactor the source, to make it more approachable to developers who fork the project
* Drop support for WordPress versions earlier than 5.x.x
* Drop support for WooCommerce versions earlier than 4.x.x
* Drop support for PHP versions earlier than 7.2

