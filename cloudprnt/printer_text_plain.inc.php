<?php
	include_once('printer_queue.inc.php');

	class Star_CloudPRNT_Text_Plain_Job extends Star_CloudPRNT_Document_Builder_Base
	{
		
		public function get_emulation()
		{
			return "text/plain";
		}
		
		public function set_font($font)
		{
		}

		public function set_text_emphasized()
		{
		}
		
		public function cancel_text_emphasized()
		{
		}
		
		public function set_text_left_align()
		{
		}
		
		public function set_text_center_align()
		{
		}
		
		public function set_text_right_align()
		{
		}
		
		public function set_codepage($codepage)
		{
		}
		
		public function add_nv_logo($keycode)
		{
		}
		
		public function set_font_magnification($width, $height)
		{
		}

		public function sound_buzzer($circuit, $pulse_ms, $delay_ms)
		{
		}
		
		public function set_text_highlight()
		{
		}
		
		public function cancel_text_highlight()
		{
		}

		public function add_qr_code($error_correction, $cell_size, $data)
		{
		}

		public function add_barcode($type, $module, $hri, $height, $data)
		{
		}

		public function cut()
		{
			$this->add_new_line(1);
		}
	}
?>