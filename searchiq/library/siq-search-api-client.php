<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class siq_search_api_client{

	
	private $wsAPIEndPoint = ""; // End point for making API call
	private $wsAPIKey;			 // API key that will be used
	private $dataParam = array('params'=>array(), "callMethod"=>'POST');
	public $errorFile;
	/**
		__construct: Default constructor for the class
	
	**/
	
	public function __construct(){
		$this->wsAPIEndPoint = SIQ_SERVER_BASE.SIQ_SERVER_API_ENDPOINT; // End point for making API call
	}
	
	/**
		serialize_params: function to serialize query_string parameters
			
		supported arguments:
			$params => parameters to be serialized
	
	**/
	
	private function serialize_params( &$params ) {
		//$query_string = http_build_query( $params );
		$query_string = "";
		if(is_array($params) && count($params) > 0) {
			foreach ($params as $k => $v) {
				$query_string .= $k . "=" . $v . '&';
			}
			$query_string = substr($query_string, 0, -1);
		}
		return $query_string; //preg_replace( '/%5B(?:[0-9]+)%5D=/', '%5B%5D=', $query_string );
	}
	
	/**
		wsAPICall: function to make an API call to the server
		
		supported arguments:
			$data = array() // array type with the following keys
				1. params 		: key for parameters to be passed to the server
				2. callMethod	: Method of the call i.e. one of GET, POST and DELETE
	
	**/
	
	private function wsAPICall( &$data = array() , $sendAPiKey = true) {
		global $wp_version, $siq_plugin;
		if(isset($data['verify']) && $data['verify'] === true){
			$apiKey = true;
			$this->wsAPIKey = $data['params']['apiKey'];
			$data['params'] = array_key_exists("id", $data['params']) ? array("id"=>$data['params']['id']): array();
		}else if(isset($data['auth']) && $data['auth'] === true){
			$apiKey = true;
		}else{
			$apiKey = $this->wsAPIKey;
		}
		$userAgent = "WordPress/".$wp_version."; ".$this->siq_get_domain();
		if(!$apiKey){
			throw new Exception('Invalid Auth Token');
		}else{
			if(count($data) == 0){
				$data = $this->dataParam;
			}
			$callParams = &$data['params'];
			$callMethod	= &$data['callMethod'];
			$callHeaders = array(
				'Content-Type' 	=> 'application/json; charset=UTF-8',
				'DW-Auth'		=> $this->siq_get_api_key(),
				'user-agent'	=> &$userAgent
			);
			if($sendAPiKey == true){
				$callHeaders = array(
					'Content-Type' 	=> 'application/json; charset=UTF-8',
					'DW-Auth'		=> $this->siq_get_api_key(),
					'user-agent'	=> &$userAgent
				);		
			}else{
				$callHeaders = array(
					'Content-Type' 	=> 'application/json; charset=UTF-8',
					'user-agent'	=> &$userAgent
				);
			}
			$callHeaders['Accept'] = 'application/json, text/plain';
			$args = array(
				'method' 		=> '',
				'timeout' 		=> $siq_plugin->getApiCallTimeout(),
				'redirection' 	=> 5,
				'httpversion' 	=> '1.0',
				'blocking' 		=> true,
				'headers' 		=> &$callHeaders,
				'cookies' 		=> array(),
				'body' 			=> array()
			);
			if($callMethod != ""){
				$args['method'] = &$callMethod;
			}
			
			$CallUrl = $this->wsAPIEndPoint.$data['callUrl'];
			
			if( ($callMethod == 'GET') && !empty($callParams) && (!isset($data['body']) || ( isset($data['body']) && $data['body'] == false) ) ) {
				$CallUrl .= '?' . $this->serialize_params( $callParams );
			} else if( $callMethod == 'POST' || $callMethod == 'PUT'  || $callMethod == 'DELETE' || (isset($data['body']) && $data['body'] == true) ) {
				$args['body'] = json_encode( $callParams );
			}
			
			$res = wp_remote_request( $CallUrl, $args );
			
			$response_status_code = wp_remote_retrieve_response_code($res);
			if ($response_status_code < 200 || $response_status_code >= 400) {
				$response_body = wp_remote_retrieve_body($res);
				$request_body = '';
				if (!empty($args['body']) && is_string($args['body'])) {
					$request_body = &$args['body'];
				}
				$siq_plugin->logAPIError($CallUrl, 
					$callMethod, 
					$request_body, 
					$response_status_code, 
					$response_body
				);
			}
			if( is_wp_error( $res ) ) {
				$msg = "";
				$msg = $res->get_error_message();
				if(strpos($msg, "Connection refused") > -1){
					$msg = "We are experiencing temporary site issues";
				}
				throw new Exception(esc_html($msg, ) , 500 );
			}else{
				$retrieve_response_code 	= wp_remote_retrieve_response_code( $res );
				$retrieve_response_message 	= wp_remote_retrieve_response_message( $res );
				
				if( ($retrieve_response_code >= 200 && $retrieve_response_code < 300) || $retrieve_response_code == 403 || $retrieve_response_code == 401) {
					$response_body = wp_remote_retrieve_body( $res );
					$responseArray = json_decode($response_body, true);
					$responseString = json_decode($response_body, false);
					if(is_array($responseArray)){
						$responseArray['success'] = true;
						return array( 'response_code' => $retrieve_response_code, 'response_body' => $responseArray, 'response_message' => $retrieve_response_message, 'response_string' => $responseString);
					}else if($response_body != "" && ($retrieve_response_code == 403 || $retrieve_response_code == 401)){
						return array( 'response_code' => $retrieve_response_code, 'response_body' => array("success"=>false, "message"=> $response_body));
					}else if($response_body != ""){
						return array( 'response_code' => $retrieve_response_code, 'response_body' => array("success"=>true, "message"=> $response_body));
					}else if($retrieve_response_message !=""){
						return array( 'response_code' => $retrieve_response_code, 'response_body' => array("success"=>true, "message"=> $retrieve_response_message));
					}else{
						throw new Exception( esc_html('Unknown Error'), intval($retrieve_response_code) );
					}
				} elseif( ! empty( $retrieve_response_message ) ) {
					$response_body = wp_remote_retrieve_body( $res );
					if($response_body != ""){
						$body 		= json_decode($response_body, true);
						if(is_array($body)){
							$message 	= $body['message'];
						}else{
							$message 	= $response_body;
						}
					}else{
						$message 	= $retrieve_response_message;
					}
					throw new Exception( esc_html($message), intval($retrieve_response_code) );
				} else if($retrieve_response_code == 0 && empty($retrieve_response_message)){
					throw new Exception( esc_html('Unable to connect to server, please check your network'), intval($retrieve_response_code) );
				}else{
					throw new Exception( esc_html('Unknown Error'), intval($retrieve_response_code) );
				}
			}
		}
	}
	/**
		makeAPICall: function to make an API call
		
		supported parameters: 
			$data: data array for the call
		
	**/
	
	public function makeAPICall(&$data, $sendAPiKey = true){
		return $this->wsAPICall($data, $sendAPiKey);
	}
	
	/**
		siq_get_api_key: function to get API key
	**/
	
	public function siq_get_api_key(){
		return $this->wsAPIKey;
	}
	
	/**
		siq_get_domain: function to get domain name
	**/
	
	public function siq_get_domain(){
		global $siq_plugin;
		$engine_info = $siq_plugin->get_siq_engine_details();
		return is_array($engine_info) && !empty($engine_info['domain']) ? $engine_info['domain'] : get_option('siteurl');
	}
	
	
	
	/**
		siq_set_api_key: function to set API key
		
		supported parameters: 
			wsAPIKey: API key Value
		
	**/
	
	public function siq_set_api_key($wsAPIKey){
		$this->wsAPIKey = $wsAPIKey;
	}
	
	/**
		siq_set_api_key: function to set API key
		
		supported parameters: 
			wsAPIKey: API key Value
		
	**/
	
	public function siq_get_api_endpoint(){
		return $this->wsAPIEndPoint;
	}
	
}
