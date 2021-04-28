<?php
	include_once('printer_queue.inc.php');

	class Star_CloudPRNT_Star_Prnt_Job extends Star_CloudPRNT_Document_StarPrntCore
	{			

		public function get_emulation()
		{
			return "application/vnd.star.starprnt";
		}

		public function add_nv_logo($keycode)
		{
			//$this->printJobBuilder .= "1B1C70".$keycode."00".self::SLM_NEW_LINE_HEX
			$this->printJobBuilder .="1B1D284C06003045".$this->str_to_hex($keycode)."0101";
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

			$command = sprintf("1B1D1911%02X%02X%02X", $circuit, $pulse_param, $delay_param)
			 						. sprintf("1B1D1912%02X0100", $circuit);

			$this->printJobBuilder .= $command;
		}

	}
?>