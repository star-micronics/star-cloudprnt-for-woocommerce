# Filters

Many filters are implemented from version 2.1 onwards, to allow external plugins to modify the content of the printed receipt.
All filters begin with the "smcpfw_" prefix, and include the name of the section that they represent.

In most cases, a WC_Order object will be passed to the filter, containing the object representing the current order. This can be used to adjust the output based on the order details.

Printer hooks with names that begin with "smcpfw_render", will be passed a $printer object, which implements the print job builder API, allowing direct generation of the printed receipt content. These can be used to add or replace sections of the receipt, with full access to the underlying device features, such as text size, logos, barcodes, cutter control etc.

## Selecting/Rendering Sections of the receipt

Several filters are available to allow external plugins to determine which sections should be printed, and in which order.
Plugins can also define their own sections if needed. Finally it is also possible for a plugin to take over rendering of the whole receipt, although not generally recommended.

### smcpfw_render_whole_receipt

Allows plugin to filly take-over rendering the whole receipt. If a plugin handles this filter, then they are responsible for handling the entire rendering and no other filters will be triggered. This is generally not recommended unless very heavy customisation is needed.

- @param bool - default filter value, set to false, but can be ignored.
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order to be printed.
- @return return `true` to indicate that this filter has rendered the receipt, and block the default receipt rendering. Return any other value to allow default rendering to continue.

### smcpfw_sections

Filter the list of receipt section to be printed. Using this filter, it's possible to disable certain sections of the receipt, change the order in which they print, or add completely new sections by inserting a new string into the array with a unique name. If inserting a completely new section, then it would generally be necessary to also implement a `smcpfw_render_{$section}` filter to handle rendering that section too.

The default list of receipt sections is (in sequence): `"header", "sub_header", "items_header", "items", "coupons", "item_totals", "items_footer", "order_info", "address", "notes", "footer", "end"`.

- @param array - an array of section name strings. This is the list of sections, in order, that are can be filtered.
- @param WC_Order - the current order to be printed.
- @return a filtered array of receipt sections to be printed in sequence.

#### Example

This example, adds a new receipt section named "signature" immediately before the "items_footer" section.

~~~php
function star_example_filter_sections($sections, $order) {
  $end_pos = array_search("items_footer", $sections, true);
  if($end_pos !== false) {
    array_splice($sections, $end_pos, 0, "signature");
  }			
  return $sections;
}
add_filter('smcpfw_sections', 'star_example_filter_sections', 10, 2);
~~~



### smcpfw_pre_{$section}

Specify some text data to be printed before a named section of the receipt, where `{$section}` is the name of the section.

- @param string - current text data to print before this section.
- @param WC_Order - the WC_Order object representing the order being printed.
- @return string - filtered text to be printed before the named section.

#### Example

To add a message to the receipt, before the footer

~~~php
function star_example_filter_pre_footer($default_value) {
  return $default_value . "\nThank you for visiting the Star store, we hope to see you again soon.\n";
}
add_filter('smcpfw_pre_footer', 'star_example_filter_pre_footer');
~~~

### smcpfw_post_{$section}

Specify some text data to be printed after a named section of the receipt, where `{$section}` is the name of the section.

- @param string - current text data to print after this section.
- @param WC_Order - the WC_Order object representing the order being printed.
- @return string - filtered text to be printed after the named section.

### smcpfw_render_pre_{$section}

Handle the rendering if needed of any pre_{$section} data. If not provided, then the data will be printed as plain text using the default font.

- @param string - Text data to be printed, after any `smcpfw_pre_{$section}` filters have been applied.
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order.
- @return return `true` to prevent the internal rendering being used.

### smcpfw_render_post_{$section}

Handle the rendering if needed of any post_{$section} data. If not provided, then the data will be printed as plain text using the default font.

- @param string - Text data to be printed, after any `smcpfw_post_{$section}` filters have been applied.
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order.
- @return return `true` to prevent the internal rendering being used.

### smcpfw_render_{$section}

Completely handle the rendering of the named section. Be aware that if fully handling the rendering of a section, the individual filters that would usually be triggered by the default renderer for that section will not be called (unless your renderer calls `apply_filters()` in turn to trigger them).

- @param boolean - Default filter result, set to `false`, return `true` instead to indicate that your rendered code has handled rendering.
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order.
- @return return `true` to indicate that your filter has handled the rendering and prevent the default rendered being called.

#### Example

This example uses the print job builder API to render a custom section named "signature" with an area for a customer to sign.

~~~php
function star_example_render_signature($banner_items, $printer, $order) {
  $printer->add_new_line(1);
  $printer->set_font_magnification(2, 1);
  $printer->add_text_line("Please Sign");
  $printer->set_font_magnification(1, 1);
  $printer->add_text_line(" _____________________________");
  $printer->add_text_line("|                             |");
  $printer->add_text_line("|                             |");
  $printer->add_text_line("|_____________________________|");
  $printer->add_new_line(1);

  return true;
}
add_filter('smcpfw_render_signature', 'star_example_render_signature', 10, 3);
~~~

## Header Filters

These filters can be used to modify the content of the receipt header section.

### smcpfw_header_logo

Filter to modify the id of the top logo that will be printed. This can be used to change the printed logo based on order content, time of day, etc.

- @param string - the current top logo id.
- @param WC_Order - the WC_Order object representing the order being printed.
- @return string - string value containing the Id of a logo that has been pre-stores in the printer FlashROM. Returning an empty value will mean that no logo is printed.

### smcpfw_header_title

Filter to modify the title text printed at the top of the receipt.

- @param string - the current receipt title value.
- @param WC_Order - the WC_Order object representing the order being printed. This can be used to modify the title, based on the order details.
- @return string - string value that will be used to print the receipt title. Returning an empty value will mean that no title is printed at all.

#### Example

An example which checks the order for a "delivery_type" meta-data key, and if present prints it's value as the title. This can be used with the "Delivery & Pickup Date Time for WooCommerce" plugin, to print either "delivery" or "pickup" as the receipt title, as appropriate.

~~~php
function star_example_filter_title($default_value, $order) {
  if($order->meta_exists("delivery_type"))
    return $order->get_meta("delivery_type");
  return $default_value;
}
add_filter('smcpfw_header_title', 'star_example_filter_title', 10, 2);
~~~

### smcpfw_render_header_title

Provides a filter that can be used to replace the default rendering of the title using the print job builder API.

- @param string - Title text that should be printed as the title, after translation and any existing 'smcpfw_header_title' filters.
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order.
- @return return `true` to block the default internal rendering of the header title.

#### Example

This example prints the title with a larger font (3x width, 2x height) and converted to upper case.

~~~php
function star_example_render_title($title, $printer) { 
  $printer->set_text_center_align();
  $printer->set_font_magnification(3, 2);

  $printer->add_text_line(mb_convert_case($title, MB_CASE_UPPER));

  $printer->set_text_left_align();
  $printer->set_font_magnification(1, 1);
  $printer->add_new_line(1);

  return true;
}
add_filter('smcpfw_render_header_title', 'star_example_render_title', 10, 2);
~~~

## Sub Header Filters

These filters apply to the `sub_header` section of the receipt.

### smcpfw_sub_header_banner

Filter the left and right parts of the banner typically printed at the start of the `sub_header` section. By Default, this is the order number printed on the left, and timestamp of the print job on the right.

- @param array - an array of two string value fields, with key names `left` and `right`, to be printed on the banner.
- @param WC_Order - the WC_Order object representing the order being printed.
- @return array - and array which should have two values, with key names `left` and `right` that will be used to generate the sub-header banner.

#### Example

Print only the current time on the left part of the banner, instead of full date and time.

~~~php
function star_example_filter_sub_header_banner($banner) {
  $time_format = get_option( 'time_format' );
  $banner["right"] = date("{$time_format}", current_time('timestamp'));
  return $banner;
}
add_filter('smcpfw_sub_header_banner', 'star_example_filter_sub_header_banner');
~~~

### smcpfw_render_sub_header_banner

Allow custom rendering of the sub-header banner.

- @param array - Array with `left` and `right` keys, representing the sun-header banner field strings'
- @param printer - an object that implements the print job builder API.
- @param WC_Order - the current order.
- @return return `true` to indict that rendering has been performed and to block the internal renderer.

#### Example

Renders the banner on two lines and centrally aligned, with the left (order number) part in a larger font.

~~~php
function star_example_render_sub_header_banner($banner_items, $printer, $order) {
  $printer->set_text_center_align();
  $printer->set_font_magnification(2, 1);
  $printer->add_text_line($banner_items["left"]);
  $printer->set_font_magnification(1, 1);
  $printer->add_text_line($banner_items["right"]);
  $printer->set_text_left_align();
  $printer->add_new_line(1);

  return true;
}
add_filter('smcpfw_render_sub_header_banner', 'star_example_render_sub_header_banner', 10, 3);
~~~
