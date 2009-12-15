<?php

class Sms extends Controller {
	
	function __construct()
	{
		parent::__construct();
	}
	
	function __destruct()
	{
		parent::__destruct();
	}
	
	//send the sms
	function send_sms() {
		$this->load->library('esendex');

		$mobile = '1112223344';
		$sms    = 'This is a test';
		$type   = 'text';		

		$result = $this->esendex->send_message($mobile, $sms, $type);
	}
}
