=== Star CloudPRNT for WooCommerce ===
Contributors: fmahmood, lawrenceowen
Tags: star, printing, printers, automated, e-commerce, store, sales, downloadable, downloads, woocommerce, restaurant, order, receipt
Requires at least: 4.7.0
Tested up to: 5.4.2
Requires PHP: 5.6
Stable tag: 1.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
* WordPress 4.7.X, 4.8.X, 4.9.X, 5.0.x, 5.1.x, 5.3.x or 5.4.x.
* WooCommerce plugin 3.0.X, 4.0.X or 4.2.X
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

= Can I print my local language characters? =

Yes, you can enable UTF-8 text encoding if you have a compatible printer model to resolve typical language specific character issues.
Please use the plugin settings page to enable UTB-8 mode.

Models with UTF-8 support are the TSP650II, mC-Print3 and mC-Print2. 
These printers cover most Latin, Cyrilic, Greek and CJK characters, although not all characters can be supported by all device models and for TSP654II a firmware update may be required. Please talk with Star for advice if necessary.

= Can I modify the print job design/layout? =

At this time, modifying the print job layout is possible only by directly editing the plugin PHP source code. This is entirely permitted by the GPLv3 license terms.
Your local Star Micronics support contact may be able to offer some assistance.

= How can I add an image to the top or end of my print? =

Images must be pre-stored inside the printer FlashROM memory in order to be printed bu this plugin. Once stored, they will have logo number/id, which can be input into the "Printer Logo Settings" settings area to enable top and/or bottom logo printing.
Storing images inside the Print FlashROM is possible with a software utility provided by Star Micronics, included with the standard indows driver package. These can be downloaded from the Star Micronics web site.

== Screenshots ==
1. Star CloudPRNT settings page.
2. Printer management page.

== Upgrade Notice ==

= 1.2.0 =
* 

= 1.1.0 = 
* Add Support for Star mC-Print3 and mC-Print2 printer ranges
* Add support for UTF-8 text encoding
* Enable text magnification.

== Changelog ==
1.1.2 - 2020-04-14
- Work around an issue that prevents printing to thermal printer models with HIX Connect interface that has earlier firmware. In this situation, the plugin will now follow the same logic as 1.0.x plugin releases.

1.1.1 - 2020-04-06
- Add support for WordPress 5.4.X

1.1.0 - 2020-03-26
- Support mC-Print31 and mC-Print21 printer models
- Enable Unicode characters sets on supported printer models (TSP654II, mC-Print31, mC-Print21)
- Support character magnification
- Tested with WordPress up to version 5.3.2 and WooCommerce 4.0.X

1.0.3 - 2018-06-19
- Replaced HTTP DELETE requests with HTTP GET requests, due to some servers not supporting this method.
- Fixed bug where print job is lost after failed HTTP GET request.
- Added support for Wordpress 4.9.X (tested up to 4.9.6).
- Changed recommended printer interface firmware to 1.4 or greater.

1.0.2 - 2017-06-27
- Added order history feature to log printed orders.
- Added support for WordPress 4.8.0 and WooCommerce 3.0.X.

1.0.1 - 2017-04-28
- Bug fixes.

1.0.0 - 2017-04-06
- Initial release.
