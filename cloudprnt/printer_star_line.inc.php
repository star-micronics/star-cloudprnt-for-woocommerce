<?php
	include_once('printer_queue.inc.php');

	class Star_CloudPRNT_Star_Line_Mode_Job extends Star_CloudPRNT_Document_StarPrntCore
	{	
		public function get_emulation()
		{
			return "application/vnd.star.line";
		}

		public function add_nv_logo($keycode)
		{
			$this->printJobBuilder .= "1B1C70".$keycode."00".self::SLM_NEW_LINE_HEX;
		}
	}
?>