# Document Builder API

Filters that allow rendering directly to a portion of the receipt, will be passed an object that implements the `Star_CloudPRNT_Document_Builder` interface.
This object can be used to add formatted content directly to the printed receipt.

## Implementations

Several implementations of this class are built-in to the plugin, to support different target printer models. In general, plugin developer should not consider the underlying implementation, however if they do need to insert raw printer command data into the document, then please call `get_emulation()` first, to recognise the underlying printer command set being used, and only insert raw device commands if you are sure that they are compatible with tta device emulation.

Additional differences may exist, due to the capabilities of the target printer, for example, dot-matrix printers do not support printing barcodes/QrCodes, and support a maximum of 2x2 font magnification. Differences in the expected result will be noted in the individual method documentation.

## Methods

### get_text_columns()

Retrieve the number of text columns (characters) that can be printed on a single line.

- @returns *integer* - the number of half-width (standard Latin) characters that can be printed on a single line of the receipt. Based on the printers reported pint width, currently selected font, and font magnification.

#### Description

The number of text columns returned will be calculated  based on the currently enabled font abd magnification settings.

The returned value is based on the standard half-width characters. If you are printing full-width characters (e.g. Japanese, Chinese characters) then these will use the equivalent of two half-width characters.

### clear_formatting()

Resets text formatting to defaults. Including the default, font, magnification, alignment etc.

### set_text_emphasized()

Enable emphasized (bold) for subsequently printed text.

### cancel_text_emphasized()

Disable emphasized (bold) text printing.

### set_text_highlight()

Enable highlighting for subsequently printed text. For monochrome/thermal printers, this will be printed as white text with a black background. For printers with two colour support (such as some dot-matrix models), this will be printed using the devices second colour.

### cancel_text_highlight()

Disable highlighting for subsequently printed text.

### set_text_underlined()

Enable underlining for subsequently printed text.

### cancel_text_underlined()

Disable underlining for subsequently printed text.

### set_text_left_align()

Switch to left-aligned text printing.

### set_text_center_align()

Switch to centrally aligned text printing.

### set_text_right_align()

Switch to right-aligned text printing.

### add_nv_logo($keycode)

Print a logo stored in printer FlashROM.

- @param `$keycode` *string* - Id of the logo to be printed.

#### Description

Trigger the printing of a graphic that has been pre-stored in your printers flashROM. Images can be stored using the Windows device configuration software for your printer, other methods are available, please contact Technical support at your local Star office if assistance is needed.

### add_feed(`$length`)

Add a vertical space to the document.

- @param `$length` *float* - Length, in mm to feed the paper by. This will be matched as closely as possible to the printers feed resolution (1/8mm steps for most models).

### add_divider(`$pattern`, `$percentage`)

Add a horizontal divider bar to the document.

- @param `$pattern` *const* - one of the build in pattern constants, see table below.
- @param `$percentage` *integer* - percentage of the page width to use for drawing the divider pattern.

Pattern const                                            | Description
:--------------------------------------------------------|:-----------------------------------
Star_CloudPRNT_Document_Builder::LINE_THIN               | A thin, solid line
Star_CloudPRNT_Document_Builder::LINE_MEDIUM             | Medium solid line
Star_CloudPRNT_Document_Builder::LINE_HEAVY              | Heavy solid line
Star_CloudPRNT_Document_Builder::LINE_DOTS_SMALL         | Row of small dots
Star_CloudPRNT_Document_Builder::LINE_DOTS_MEDIUM        | Row of medium size dots
Star_CloudPRNT_Document_Builder::LINE_DOTS_HEAVY         | Row of large dots
Star_CloudPRNT_Document_Builder::LINE_DASH_SMALL         | Row of small dashes
Star_CloudPRNT_Document_Builder::LINE_DASH_MEDIUM        | Row of medium dashes
Star_CloudPRNT_Document_Builder::LINE_DASH_HEAVY         | Row of heavy dashes

### set_font(`$font`)

Select a built-in printer font.

- @param `$font` *const* - Select which printer font is required for subsequent text printing `Star_CloudPRNT_Document_Builder::FONT_A` or `Star_CloudPRNT_Document_Builder::FONT_B`

#### Descriptions

Most Star printers have two built-in device fonts. By default, the larger font "A" is selected.

Font    | Size
:-------|:-------------
A       | 12 x 24 dots (1.5 x 3mm)
B       | 9 x 24 dots (1.1 x 3mm)

Both printer fonts may be magnified horizontally and/or vertically as needed to allow a variety of font sizes.

### set_font_magnification(`$width`, `$height`)

Specify the font size

- @param `$width` *integer* - required font width multiplier, from 1 to 6.
- @param '$height' *integer* - required font height multiplier, from 1 to 6.

#### Description

The built-in printer device fonts can be scaled by whole values only. Please combine font scaling, with different base font sizes to achieve the widest variety of font sizes.

### add_text(`$text`)

Add text to the document, on the current line.

- @param `$text` *string* - String data to be added to the document.

### add_text_line(`$text`)

Add a line of text to the current document.

- @param '$text' *string* - String data to add to the document.

### add_new_line(`$quantity`)

Add one or more empty lines to the document.

- @param `$quantity` *integer* - the number of lines to add to the document.

### add_word_wrapped_text_line(`$text`)

Add text that will be automatically word-wrapped, if needed, to fit the current page.

- @param '$text' *string* - String data to add to the document.

#### Description

This method expects to be adding output to a new line, so usually should not be called after partially printing a text line with `add_text()`.

Note that this method is implemented using the PHP `wordwrap()` function, that is not aware of full-width characters, such as Japanese and Chinese glyphs. Therefore, you may not get exactly the expected results if printing languages that contain full-width characters.

### add_two_columns_text_line(`$left`, `$right`)

Print a line formatted in to two columns.

- @param `$left` *string* - text data to include in the left hand column.
- @param `$right` *string* - text data to include in the right-hand column.

### add_qr_code(`$error_correction`, `$cell_size`, `$data`)

Add a QrCode to the document.

- @param `$error_correction` *const* - specify the level of error correction to include in the generated QrCode.
- @param `$cell_size` *integer* - Size of individual QrCode cells, in printer dots (1/8mm). Values from 1 to 8 are allowed.
- @param `$data` *string* - data to be encoded into the QrCode.

#### Description

Supported Error correction levels are:

Constant | Correction Level
:--------|:----------------
Star_CloudPRNT_Document_Builder::QR_ERROR_CORRECTION_LOW         | 7%
Star_CloudPRNT_Document_Builder::QR_ERROR_CORRECTION_MEDIUM      | 15%
Star_CloudPRNT_Document_Builder::QR_ERROR_CORRECTION_QUARTER     | 25%
Star_CloudPRNT_Document_Builder::QR_ERROR_CORRECTION_HIGH        | 30%

A greater level of error correction will increase teh size of the printed QrCode.

> **Note** Only Star thermal printers support QrCode printing, this will be ignored by dot-matrix printers.

### add_barcode(`$type`, `$module`, `$hri`, `$height`, `$data`)

Add a 2D barcode to the document.

- @param `$type` *const* - Specify the type of barcode to be printed. See below for supported types.
- @param `$module` *integer* - Specify the base module size (width of individual bars). Supported module sizes vary depending on barcode type, see below.
- @param `$height` *integer* - Height of the barcode in printer dots (1/8mm), can range from 8 (1mm) to 255 (32mm).
- @param `$data` *string* - data to be encoded in the barcode. Not that data content and length requirements vary depending on the barcode type, see below.

#### Description

Barcode sizes will vary depending on the data length, data content (for types with variable encodings/compression) and chosen module size. The module value and data allowed varies depending on the barcode type, please see the tables below.

> **Note** Only Star thermal printers support Barcode printing, this will be ignored by dot-matrix printers.

##### Barcode types and module/data limitations

Barcode Type | $type           | $module range | $data requirements
:------------|:----------------|:---------|:------------------------
UPC-E        | Star_CloudPRNT_Document_Builder::BARCODE_UPC_E    | 1 - 3 (module 2 to 4 dots) | 11 characters, numeric
UPC-A        | Star_CloudPRNT_Document_Builder::BARCODE_UPC_A    | 1 - 3 (module 2 to 4 dots) | 11 characters, numeric
JAN/EAN8     | Star_CloudPRNT_Document_Builder::BARCODE_EAN_8    | 1 - 3 (module 2 to 4 dots) | 7 characters, numeric
JAN/EAN13    | Star_CloudPRNT_Document_Builder::BARCODE_EAN_13   | 1 - 3 (module 2 to 4 dots) | 12 characters, numeric
Code 39      | Star_CloudPRNT_Document_Builder::BARCODE_CODE_39  | 1 - 9 (see table below)    | Uppercase alphanumeric, and " ", "$", "%", "+", "-", ".", "/"
Interleaved 2 of 5 | Star_CloudPRNT_Document_Builder::BARCODE_IFT| 1 - 9 (see table below)    | Even number of numeric characters (0 added to the start in case of odd number)
Code 128     | Star_CloudPRNT_Document_Builder::BARCODE_CODE_128 | 1 - 3 (module 2 to 4 dots) | Full Ascii
Code 93      | Star_CloudPRNT_Document_Builder::BARCODE_CODE_93  | 1 - 3 (module 2 to 4 dots) | Full Ascii
NW-7         | Star_CloudPRNT_Document_Builder::BARCODE_NW_7     | 1 - 9 (see table below)    | Numeric characters and "A" - "D", "a" - "d", "$", "+", "-", ".", "/", ":"
GS1-128      | Star_CloudPRNT_Document_Builder::BARCODE_GS1_128  | 1 - 6 (module 1 - 6 dots)  | 2 to 255 characters, full Ascii
GS1 DataBar<br/> Omnidirectional | Star_CloudPRNT_Document_Builder::BARCODE_GS1_DATA_BAR_OMNI  | 1 - 6 (module 1 - 6 dots) | 13 characters, numeric
GS1 DataBar<br/> Truncated       | Star_CloudPRNT_Document_Builder::BARCODE_GS1_DATA_BAR_TRUNC | 1 - 6 (module 1 - 6 dots) | 13 characters, numeric
GS1 DataBar<br/>Limited          | Star_CloudPRNT_Document_Builder::BARCODE_GS1_DATA_BAR_LIM   | 1 - 6 (module 1 - 6 dots) | 13 characters, numeric, first character is "0" or "1"
GS1-DataBar<br/>Expanded         | Star_CloudPRNT_Document_Builder::BARCODE_GS1_DATA_BAR_EXP   | 1 - 6 (module 1 - 6 dots) | 2 to 255 characters, most Ascii, refer to Star command manual spec for limitations.

###### Module values for Code 93, NW-7 and Interleaved 2 of 5 barcodes

$module | Code 39/NW-7<br/> Narrow Bar/Wide Bar (dots) | Interleaved 2 of 5<br/>Narrow Bar/Wide Bar (dots)
:-------|----------------------------------------------|---------------------------------
1       | 2/6                                          | 2/5
2       | 3/9                                          | 4/10
3 | 4/12 (widest) | 6/15 (widest)
4 | 2/5 | 2/4 (narrowest)
5 | 3/8 | 4/8
6 | 4/10 | 6/12
7 | 2/4 (narrowest) | 2/6
8 | 3/6 | 3/9
9 | 4/8 | 4/12

### sound_buzzer(`$circuit`, `$pulse_ms`, `$delay_ms`)

Sound a buzzer if one is connected.

- @param `$circuit`, *const* - specify which of the 2 control circuits the buzzer is connected to. This is usually circuit 1, unless custom cabling has been used. Allowed values are `Star_CloudPRNT_Document_Builder::DK_CIRCUIT_1`, and `Star_CloudPRNT_Document_Builder::DK_CIRCUIT_2`
- @param `$pulse_ms` *integer* - Time to sound the buzzer in milliseconds, 1 to 5100
- @param `$delay_ms` *integer* - Time to delay after sounding in milliseconds, 1 to 5100

#### Description

This can be called multiple times in sequence to create variable buzz sound patterns.
The printer will delay printing while sounding the external buzzer, therefore it is usually advised only to use this method before printing, or after the print job end section.

### get_emulation()

Discover the underlying command set/emulation used by the printer that will receive this document.

- @return *string* - media-type style string describing the underlying device command-set/emulation.

#### Description

Use this, to verify that your device has specific features that you want to use (e.g. dot-matrix models do not support barcode/QrCode printing). If you plan to use the `add_hex()` method to insert raw command data into a document, then please use `get_emulation()` first, to verify that the target device is compatible with those commands.

Currently supported emulations that may be returned are:

Emulation                     | Device notes
:-----------------------------|:---------------------------------------
application/vnd.star.starprnt | The device uses the StarPRNT command set, such as the mC-Print range.
application/vnd.star.line | The device uses the thermal printer version of Star Line Mode, such as TSP650II, TSP700II, TSP800II.
application/vnd.star.linematrix | The device uses the dot-matrix version of Star Line Mode, such as the SP700.
application/vnd.star.starprntcore | This is used to represent the common base command set, shared by both StarPRNT and Star Line Mode devices. It is used as a base for implementing other builder implementations, and should not be provided to filters for any currently supported printer.
text/plain | A fallback in case the target device supports text based print jobs but not a known command set. This should not be provided to any filters when using any current printer models. But it is possible in case of future printer models that to not support at-least StarPRNT Core commands - in this case, it is likely that you should update your plugin to gain full support for the new device.

### add_hex(`$hex`)

Add daw device command data to a document.

- @param `$hex` *string* - a string in hexadecimal format, of raw command data to insert into a document.

#### Description

Inserts raw device command data into a document, please always call `get_emulation()` first, to verify that the target printer can support the commands that you intend to send.

Data, must be in hexadecimal format, to allow it to be passed as plain Ascii data without encoding issues. For example `"1B7A00"` is the StarPRNT Core command to select 3mm line spacing.

### set_codepage(`$codepage`)

Set the target device codepage (text encoding)

- @param `$codepage` *string* - This can be either "UTF-8" or "1252" to select the required printer text encoding. Note that this will generally be called by the plugin before rendering of the receipt begins, so it is not recommended to call this method from filters.
