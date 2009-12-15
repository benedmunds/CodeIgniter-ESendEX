<?php
/**
* Name:  ESendEx
* 
* Author:   Ben Edmunds
*           ben.edmunds@gmail.com
*           @benedmunds
*
* Location: http://github.com/benedmunds/esendex
*          
* Created:  12.08.2009 
* 
* Requirements: curl
* 
* Description:  Modified ESendEx library to send text messages through the ESendEx API with CodeIgniter.  Provides basic sms sending capability.  Original license is below.
* 
*/

/*
Name:			EsendexSendService.php
Description:	Esendex SendService Web Service PHP Wrapper
Documentation: 	http://www.esendex.com/isSecure/messenger/formpost/SendServiceNoHeader.asmx
				http://www.esendex.com/isSecure/messenger/formpost/QueryStatus.aspx

Copyright (c) 2007 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

class esendex extends EsendexFormPostUtilities
{
	var $username;
	var $password;
	var $accountReference;
	protected $ci;

	function __construct($isSecure = false, $certificate = "")
	{
		parent::EsendexFormPostUtilities( $isSecure, $certificate );
		
		//get the CI super object
		$this->ci =& get_instance();
		
		$this->ci->load->config('esendex');
		$this->username         = $this->ci->config->item('esendex_username');
		$this->password         = $this->ci->config->item('esendex_password');
		$this->accountReference = $this->ci->config->item('esendex_account_reference');		

		if ( $isSecure )
		{
			define( "SEND_SMS_URL", "https://www.esendex.com/secure/messenger/formpost/SendSMS.aspx" );
			define( "SMS_STATUS_URL", "https://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
		
		else
		{
			define( "SEND_SMS_URL", "http://www.esendex.com/secure/messenger/formpost/SendSMS.aspx" );
			define( "SMS_STATUS_URL", "http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx" );
		}
	}

	function send_message( $recipient, $body, $type )
	{

		$parameters['username']  = $this->username;
		$parameters['password']  = $this->password;
		$parameters['account']   = $this->accountReference;
		$parameters['recipient'] = $recipient;
		$parameters['body']      = $body;
		$parameters['type']      = $type;
		
		$parameters['plainText'] = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function send_message_full( $originator, $recipient, $body, $type, $validityPeriod )
	{
		$parameters['username']       = $this->username;
		$parameters['password']       = $this->password;
		$parameters['account']        = $this->accountReference;
		$parameters['originator']     = $originator;
		$parameters['recipient']      = $recipient;
		$parameters['body']           = $body;
		$parameters['type']           = $type;
		$parameters['validityPeriod'] = $validityPeriod;
		
		$parameters['plainText']      = "1";

		return $this->FormPost( $parameters, SEND_SMS_URL );
	}

	function get_message_status($messageID)
	{
		$parameters['username']      = $this->username;
		$parameters['password']      = $this->password;
		$parameters['account']       = $this->accountReference;
		$parameters['messageID']     = $messageID;
		
		$parameters['plainText']     = "1";
		
		return $this->FormPost( $parameters, SMS_STATUS_URL );
	}
}



/*
Name:			FormPostUtilities.php
Description:	Esendex PHP HTTP Form Post Utilities
Documentation: 	https://www.esendex.com/isSecure/messenger/formpost/SendServiceNoHeader.asmx

Copyright (c) 2004/2005 Esendex®

If you have any questions or comments, please contact:

support@esendex.com
http://www.esendex.com/support
*/

class EsendexFormPostUtilities
{
	var $isSecure;
	var $certificate;
	
	function EsendexFormPostUtilities( $isSecure = false, $certificate = "" )
	{
		$this->isSecure = $isSecure;
		$this->certificate = $certificate;
	}

	function FormPost( $dataStream, $url )
	{
		$postFields = "";
		$port = 80;

		foreach ( $dataStream as $key => $value )
		{
			if( !empty( $key ) && !empty( $value ) )
			{
				if ( !empty( $postFields ) ) 
				{
					$postFields.= "&";
				}
				
				$postFields.= $key."=".urlencode( $value );
			}
		}
		
		$curlHandle = curl_init();    							// Initialise the curl handle.
		curl_setopt( $curlHandle, CURLOPT_URL, $url ); 			// Set the post URL.
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
		curl_setopt($curlHandle, CURLOPT_POST, 1); 
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($curlHandle, CURLOPT_PORT, 443);
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curlHandle, CURLOPT_FAILONERROR, 1);
		
		$result = curl_exec( $curlHandle ); 						// run the whole process

		curl_setopt( $curlHandle, CURLOPT_RETURNTRANSFER, 0 );
		
		switch(curl_errno($curlHandle)) {
			case 0:		break;
			default: 	return false;
			            break;
		}
		curl_close( $curlHandle );

		return $this->ParseResult( $result );
	}
	
	function ParseResult( $result )
	{
		$results = explode( "\r\n", $result );
		
		$index = count( $results );

		$i = 0;
		$j = 0;

		while( $i < $index )
		{
			$ampersandPosition = strpos( $results[$i], "&" );

			if( $ampersandPosition != false )
			{
				$values[$j] = explode( "&", $results[$i] );
				$results[$i] = $this->GetKeyValuePairs( $values[$j] );
				$j++;
			}
			$i++;
		}

		//Get the message and key/value pair elements from the results.
		$messages = $this->GetMessagesArrays( $results );
		$keyValuePairs = $this->GetKeyValuePairs( $results );

		if( !is_array( $messages ) )
		{
			return $keyValuePairs;
		}

		$keyValuePairs['Messages'] = $messages;
		
		return $keyValuePairs;
	}

	function GetKeyValuePairs( $results )
	{
		$i = 0;
		$j = 0;
		$response = "";
		$index = count( $results );

		while( $i < $index )
		{
			if( !is_array( $results[$i] ) )
			{
				$equalsPosition = strpos( $results[$i], "=" );

				if( $equalsPosition != false )
				{
					$resultKey = substr( $results[$i], 0, strpos( $results[$i], "=" ) );
					$resultValue = urldecode( substr( $results[$i], $equalsPosition + 1, strlen( $results[$i] ) - $equalsPosition - 1 ) );

					$response[$resultKey] = $resultValue;
				}
			}
			$i++;
		}
		
		return $response;
	}

	function GetMessagesArrays( $results )
	{
		$i = 0;
		$j = 0;
		
		$index = count( $results );
		
		$messages;

		while( $i < $index )
		{
			if( is_array( $results[ $i ] ) )
			{
				$messages[$j] = $results[$i];
				
				$j++;
			}
			$i++;
		}

		$result = "";
		
		if ( $j > 0 )
		{
			$result = $messages;
		}
		
		return $result;
	}
}
?>
