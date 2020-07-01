<?php
	include_once('printer_queue.inc.php');

	class Star_CloudPRNT_Star_Prnt_Job
	{
		const SLM_NEW_LINE_HEX = "0A";
		const SLM_SET_EMPHASIZED_HEX = "1B45";
		const SLM_CANCEL_EMPHASIZED_HEX = "1B46";
		const SLM_SET_LEFT_ALIGNMENT_HEX = "1B1D6100";
		const SLM_SET_CENTER_ALIGNMENT_HEX = "1B1D6101";
		const SLM_SET_RIGHT_ALIGNMENT_HEX = "1B1D6102";
		const SLM_FEED_FULL_CUT_HEX = "1B6402";
		const SLM_FEED_PARTIAL_CUT_HEX = "1B6403";
		const SLM_CODEPAGE_HEX = "1B1D74";
		
		private $printerMac;
		private $printerMeta;
		private $tempFilePath;
		private $printJobBuilder = "";
		
		public function __construct($printer, $tempFilePath)
		{
			$this->printerMeta = $printer;
			$this->printerMac = $printer['printerMAC'];
			$this->tempFilePath = $tempFilePath;
			$printJobBuilder = "";
		}
		
		private function str_to_hex($string)
		{
			$hex = '';
			for ($i = 0; $i < strlen($string); $i++)
			{
				$ord = ord($string[$i]);
				$hexCode = dechex($ord);
				$hex .= substr('0'.$hexCode, -2);
			}
			return strToUpper($hex);
		}
		
		public function set_text_emphasized()
		{
			$this->printJobBuilder .= self::SLM_SET_EMPHASIZED_HEX;
		}
		
		public function cancel_text_emphasized()
		{
			$this->printJobBuilder .= self::SLM_CANCEL_EMPHASIZED_HEX;
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
			//$this->printJobBuilder .= "1B1C70".$keycode."00".self::SLM_NEW_LINE_HEX
			$this->printJobBuilder .="1B1D284C06003045".$this->str_to_hex($keycode)."0101";
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
			
			$this->printJobBuilder .= "1B69"."0".$h."0".$w;
		}		
		
		public function add_hex($hex)
		{
			$this->printJobBuilder .= $hex;
		}
		
		public function add_text($text)
		{
			$this->printJobBuilder .= $this->str_to_hex($text);
		}
		
		public function add_text_line($text)
		{
			$this->printJobBuilder .= $this->str_to_hex($text).self::SLM_NEW_LINE_HEX;
		}
		
		public function add_new_line($quantity)
		{
			for ($i = 0; $i < $quantity; $i++)
			{
				$this->printJobBuilder .= self::SLM_NEW_LINE_HEX;
			}
		}
		
		public function printjob($copies)
		{
			$fh = fopen($this->tempFilePath, 'w');
			fwrite($fh, hex2bin($this->printJobBuilder.self::SLM_FEED_PARTIAL_CUT_HEX));
			//fwrite($fh, hex2bin($this->printJobBuilder));
			fclose($fh);
			star_cloudprnt_queue_add_print_job($this->printerMac, $this->tempFilePath, $copies);
			unlink($this->tempFilePath);
		}
	}
?>