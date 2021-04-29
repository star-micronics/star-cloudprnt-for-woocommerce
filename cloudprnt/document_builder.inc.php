<?php
	include_once('printer_queue.inc.php');

	interface Star_CloudPRNT_Document_Builder
	{
		const FONT_A = 0;
		const FONT_B = 1;

		const QR_ERROR_CORRECTION_LOW			= 0;
		const QR_ERROR_CORRECTION_MEDIUM  = 1;
		const QR_ERROR_CORRECTION_QUARTER = 2;
		const QR_ERROR_CORRECTION_HIGH    = 3;

		const BARCODE_UPC_E								= 0;
		const BARCODE_UPC_A								= 1;
		const BARCODE_EAN_8								= 2;
		const BARCODE_EAN_13							= 3;
		const BARCODE_CODE_39							= 4;
		const BARCODE_IFT									= 5;
		const BARCODE_CODE_128						= 6;
		const BARCODE_CODE_93							= 7;
		const BARCODE_NW_7								= 8;
		const BARCODE_GS1_128							= 9;
		const BARCODE_GS1_DATA_BAR_OMNI		= 10;
		const BARCODE_GS1_DATA_BAR_TRUNC	= 11;
		const BARCODE_GS1_DATA_BAR_LIM		= 12;
		const BARCODE_GS1_DATA_BAR_EXP		= 13;
		
		const DK_CIRCUIT_1								= 1;
		const DK_CIRCUIT_2                = 2;

		public function get_emulation();
		public function get_text_columns();
		public function set_text_emphasized();
		public function cancel_text_emphasized();
		public function set_text_left_align();
		public function set_text_center_align();
		public function set_text_right_align();
		public function set_codepage($codepage);
		public function add_nv_logo($keycode);
		public function set_font($font);
		public function set_font_magnification($width, $height);
		public function add_hex($hex);
		public function add_text($text);
		public function add_text_line($text);
		public function add_new_line($quantity);
		public function add_word_wrapped_text_line($text);
		public function add_two_columns_text_line($left, $right);
		public function sound_buzzer($circuit, $pulse_ms, $delay_ms);
		public function set_text_highlight();
		public function cancel_text_highlight();
		public function add_qr_code($error_correction, $cell_size, $data);
		public function add_barcode($type, $module, $hri, $height, $data);
		public function cut();
	}
?>