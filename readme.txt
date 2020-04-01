=== Star CloudPRNT for WooCommerce ===
Contributors: fmahmood, lawrenceowen
Tags: star, printing, printers, automated, e-commerce, store, sales, downloadable, downloads, woocommerce, restaurant, order, receipt
Requires at least: 4.7.0
Tested up to: 5.4.0
Requires PHP: 5.6
Stable tag: 1.1.0
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

= Minimum Requirements =
* PHP 5.6 or greater.
* WordPress 4.7.X, 4.8.X, 4.9.X, 5.0.x, 5.1.x or 5.3.x.
* WooCommerce plugin 2.5.X, 2.6.X, 3.0.X or 4.0.X.
* Star TSP650II, TSP700II, TSP800II or SP700 series printer with a IFBD-HI01X/HI02X interface.
* Recommended printer interface firmware 1.4 or greater.

== Screenshots ==
1. Star CloudPRNT settings page.
2. Printer management page.

== Changelog ==
1.1.0 - 2020-03-26
- Support mC-Print31 and mC-Print21 printer models
- Enable Unicode characters sets on supported printer modesl (TSP654II, mC-Print31, mC-Print21)
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
