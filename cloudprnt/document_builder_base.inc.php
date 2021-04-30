<?php
	include_once('printer_queue.inc.php');

	abstract class Star_CloudPRNT_Document_Builder_Base implements Star_CloudPRNT_Document_Builder
	{
		const SLM_NEW_LINE_HEX = "0A";

		protected $printerMac;
		protected $printerMeta;
		private $tempFilePath;
		protected $printJobBuilder = "";
		
		public function __construct($printerInfo, $tempFilePath)
		{
			$this->printerMeta = $printerInfo;
			$this->printerMac = $printerInfo["printerMAC"];
			$this->tempFilePath = $tempFilePath;
			$printJobBuilder = "";
			
		}
		
		protected function str_to_hex($string)
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
		
		public function get_text_columns()
		{
			return 0;
		}

		function clear_formatting()
		{
			$this->cancel_text_emphasized();
			$this->cancel_text_highlight();
			$this->cancel_text_underlined();
			$this->set_text_left_align();
			$this->set_font_magnification(1, 1);
			$this->set_font(Star_CloudPRNT_Document_Builder::FONT_A);
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
		
		public function add_word_wrapped_text_line($text)
		{
			$this->add_text_line($text);
		}

		public function add_two_columns_text_line($left, $right)
		{
			$cols = $this->get_text_columns();
			if($cols <= 0) {
				$this->add_word_wrapped_text_line($left);
				$this->add_word_wrapped_text_line($right);
			}
			else
			{
				$total_characters = mb_strwidth($left) + mb_strwidth($right);
				$total_whitespace = $cols - $total_characters;

				if ($total_whitespace > 0)
					$this->add_text_line($left . str_repeat(" ", $total_whitespace) . $right);
				else {
					$this->add_word_wrapped_text_line($left);
					$this->add_word_wrapped_text_line($right);
				}
			}

		}

		public function add_new_line($quantity)
		{
			for ($i = 0; $i < $quantity; $i++)
			{
				$this->printJobBuilder .= self::SLM_NEW_LINE_HEX;
			}
		}
		
		public function printJob($copies)
		{
			$fh = fopen($this->tempFilePath, 'w');
			fwrite($fh, hex2bin($this->printJobBuilder));
			fclose($fh);
			
			star_cloudprnt_queue_add_print_job($this->printerMac, $this->tempFilePath, $copies);
			unlink($this->tempFilePath);
		}
	}
?>