<?php
	include_once('printer_queue.inc.php');

	class Star_CloudPRNT_Document_StarPrntCore extends Star_CloudPRNT_Document_Builder_Base
	{
		const SLM_SET_EMPHASIZED_HEX = "1B45";
		const SLM_CANCEL_EMPHASIZED_HEX = "1B46";
		const SLM_SET_UNDERLINED_HEX = "1B2D01";
		const SLM_CANCEL_UNDERLINED_HEX = "1B2D00";
		const SLM_SET_LEFT_ALIGNMENT_HEX = "1B1D6100";
		const SLM_SET_CENTER_ALIGNMENT_HEX = "1B1D6101";
		const SLM_SET_RIGHT_ALIGNMENT_HEX = "1B1D6102";
		const SLM_FEED_FULL_CUT_HEX = "1B6402";
		const SLM_FEED_PARTIAL_CUT_HEX = "1B6403";
		const SLM_CODEPAGE_HEX = "1B1D74";
		
		protected $currentFontWidth = 12;
		protected $currentFontWidthMag = 1;

		public function get_emulation()
		{
			return "application/vnd.star.starprntcore";
		}

		public function add_word_wrapped_text_line($text)
		{
			$cols = $this->get_text_columns();

			if($cols <= 0) {
				$this->add_text_line($text);
			} else {
				$this->add_text_line(wordwrap($text, $cols, "\n", true));
			}
		}

		public function get_text_columns()
		{
			// Calculate the number of printable text columns, based on teh currently selected font size, magnification and print area
			$print_area = $this->printerMeta["printWidth"];
			$char_size = $this->currentFontWidth * $this->currentFontWidthMag;

			return intval($print_area/$char_size);
		}

		public function set_text_emphasized()
		{
			$this->printJobBuilder .= self::SLM_SET_EMPHASIZED_HEX;
		}
		
		public function cancel_text_emphasized()
		{
			$this->printJobBuilder .= self::SLM_CANCEL_EMPHASIZED_HEX;
		}
		
		public function set_text_underlined()
		{
			$this->printJobBuilder .= self::SLM_SET_UNDERLINED_HEX;
		}

		public function cancel_text_underlined()
		{
			$this->printJobBuilder .= self::SLM_CANCEL_UNDERLINED_HEX;
		}

		public function set_text_left_align()
		{
			$this->printJobBuilder .= self::SLM_SET_LEFT_ALIGNMENT_HEX;
		}
		
		public function set_text_center_align()
		{
			$this->printJobBuilder .= self::SLM_SET_CENTER_ALIGNMENT_HEX;
		}
		
		public function set_text_right_align()
		{
			$this->printJobBuilder .= self::SLM_SET_RIGHT_ALIGNMENT_HEX;
		}
		
		public function set_codepage($codepage)
		{
			if($codepage == "UTF-8") {
				$this->printJobBuilder .= "1b1d295502003001"."1b1d295502004000";
			} elseif ($codepage == "1252") {
				$this->printJobBuilder .= self::SLM_CODEPAGE_HEX."20";
			} else {
				$this->printJobBuilder .= self::SLM_CODEPAGE_HEX.$codepage;
			}
		}
		
		public function add_nv_logo($keycode)
		{
			// No Star Prnt Core 
		}
		
		public function set_font($font)
		{
			if($font == Star_CloudPRNT_Document_Builder::FONT_B) {
				$this->printJobBuilder .= "1B1E4601";
				$this->currentFontWidth = 9;
			} else {
				$this->printJobBuilder .= "1B1E4600";
				$this->currentFontWidth = 12;
			}
		}

		public function set_font_magnification($width, $height)
		{
			$w = 0;
			$h = 0;

			if($width <= 1) {
				$w = 0;
			} elseif ($width >= 6) {
				$w = 5;
			} else {
				$w = $width - 1;
			}
			
			if($height <= 1) {
				$h = 0;
			} elseif ($height >= 6) {
				$h = 5;
			} else {
				$h = $height - 1;
			}
			
			$this->currentFontWidthMag = $w + 1;

			$this->printJobBuilder .= "1B69"."0".$h."0".$w;
		}
		
		public function sound_buzzer($circuit, $pulse_ms, $delay_ms)
		{
			$circuit = intval($circuit);
			if($circuit < 1) $circuit = 1;
			if($circuit > 2) $circuit = 2;
			
			$pulse_param = $pulse_ms / 20;
			$delay_param = $delay_ms / 20;
			
			if($pulse_param <= 0) $pulse_param = 0;
			if($pulse_param >= 255) $pulse_param = 255;
			
			if($delay_param <= 0) $delay_param = 0;
			if($delay_param >= 255) $pulse_param = 255;
			
			$command = sprintf("1B1D07%02X%02X%02X", $circuit, $pulse_param, $delay_param);
			$this->printJobBuilder .= $command;
		}
		
		public function set_text_highlight()
		{
			$this->printJobBuilder .= "1B34";
		}
		
		public function cancel_text_highlight()
		{
			$this->printJobBuilder .= "1B35";
		}

		public function add_divider($pattern, $percentage)
		{
			if($percentage <= 0)
				return;
			if($percentage > 100)
				$percentage = 100;

			$k = intval(($this->printerMeta["printWidth"] / 3) * ($percentage/100));

			$n1 = intval($k % 256);
			$n2 = intval($k/256);

			$pattern_hex = "8000";
			$feed = 3;

			switch($pattern) {
				case self::LINE_THIN:
					$pattern_hex = "80"; $feed = 3; break;
				case self::LINE_MEDIUM:
					$pattern_hex = "C0"; $feed = 6; break;
				case self::LINE_HEAVY:
					$pattern_hex = "E0"; $feed = 9; break;
				case self::LINE_DOTS_SMALL:
					$pattern_hex = "8000"; $feed = 3; break;
				case self::LINE_DOTS_MEDIUM:
					$pattern_hex = "C0C00000"; $feed = 6; break;
				case self::LINE_DOTS_HEAVY:
						$pattern_hex = "E0E0E0000000"; $feed = 9; break;			
				case self::LINE_DASH_SMALL:
					$pattern_hex = "8080808000000000"; $feed = 3; break;
				case self::LINE_DASH_MEDIUM:
						$pattern_hex = "C0C0C0C0C0C0C0C00000000000000000"; $feed = 6; break;
				case self::LINE_DASH_HEAVY:
						$pattern_hex = "E0E0E0E0E0E0E0E0E0E0E0E0E0E0E0E000000000000000000000000000000000"; $feed = 6; break;
				case self::LINE_DOTS_HEAVY_SHIFT:
							$pattern_hex = "000000E0E0E0"; $feed = 9; break;	
			}

			$full_seq = str_repeat($pattern_hex, intval($k/(strlen($pattern_hex)/2)) + 1);
			$full_seq = substr($full_seq, 0, $k * 2);

			$this->add_hex(sprintf("1B4B%02X%02X", $n1, $n2));
			$this->add_hex($full_seq);
			$this->add_hex(sprintf("1B49%02X", $feed));
		}

		function add_feed($length)
		{
			if($length <= 0)
				return;

			$length_dots = intval($length * 8);

			for(; $length_dots > 0; $length_dots -= 255) {
				if ($length_dots >= 255)
					$this->add_hex(sprintf("1B49%02X", 255));
				else
				$this->add_hex(sprintf("1B49%02X", $length_dots));
			}
		}

		public function add_qr_code($error_correction, $cell_size, $data)
		{
			$model = 2;
			if($error_correction < 0) $error_correction = 0;
			if($error_correction > 3) $error_correction = 3;
      if($cell_size < 1) $cell_size = 1;
			if($cell_size > 8) $cell_size = 8;
			$data_length = strlen($data);

			$set_model = sprintf("1B1D795330%02X", $model);
			$set_error_level = sprintf("1B1D795331%02X", $error_correction);
			$set_cell_size = sprintf("1B1D795332%02X", $cell_size);
			$set_data_prefix = sprintf("1B1D79443100%02X%02X", fmod($data_length, 256), intval($data_length / 256));
			$print = "1B1D7950";

			$this->printJobBuilder .= $set_model
															. $set_error_level
															. $set_cell_size
															. $set_data_prefix
															. $this->str_to_hex($data)
															. $print;
		}

		public function add_barcode($type, $module, $hri, $height, $data)
		{
			$n2 = 1; 
			$n3 = 1;

			if($type < 0 || $type > 13)	return;							// Invalid barcode type
			if($hri) $n2 = 2;																// Print human-readable characters under the barcode
			
			if($type == 0 || $type == 1 || $type == 3 || $type == 4 || $type == 6 || $type == 7)		// UPC-E, UPC-A, JAN/EAN8, JAN/EAN13, Code128, Code 93
			{
				$n3 = $module - 1;
				if($n3 < 1) $n3 = 1;
				if($n3 > 3) $n3 = 3;
			}
			elseif ($type == 4 || $type == 5 || $type == 8)		// Code 93, ITF, NW-7
			{
				$n3 = $module;
				if($n3 < 1) $n3 = 1;
				if($n3 > 9) $n3 = 9;
			}
			elseif ($type >= 9 && $type <= 13)		// GS1-128, GS1 DataBar
			{
				$n3 = $module;
				if($n3 < 1) $n3 = 1;
				if($n3 > 6) $n3 = 6;
			}

			if($height < 8) $height = 8;					// Minimum 1mm height
			if($height > 255) $height = 255;			// Max 32mm height

			$print_bc = sprintf("1B62%02X%02X%02X%02X", $type, $n2, $n3, $height)
									. $this->str_to_hex($data)
									. "1E";

			$this->printJobBuilder .= $print_bc;
		}

		public function cut()
		{
			$this->printJobBuilder .= self::SLM_FEED_PARTIAL_CUT_HEX;
		}

	}
?>