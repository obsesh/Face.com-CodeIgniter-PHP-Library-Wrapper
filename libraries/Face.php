<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Face {
	public function __construct($params)
	{
		//initialize the CI super-object
		$this->_ci =& get_instance();

		//load config
		$this->_ci->load->config('face', TRUE);

		//get settings from config
		$this->name		= $this->_ci->config->item('FACE_NAME', 'face');
		$this->key		= $this->_ci->config->item('FACE_KEY', 'face');
		$this->secret	= $this->_ci->config->item('FACE_SECRET', 'face');

		//initialize the client
		$this->_face = new FaceRestClient($this->key, $this->secret);
	}

	/**
	 * __call
	 *
	 * @desc Interface with rest client
	 *
	 */
	public function __call($method, $arguments = array())
	{
		if (!method_exists( $this->_face, $method) )
		{
			throw new Exception('Undefined method Face::' . $method . '() called');
		}

		if (!is_array($arguments))
		{
			$arguments = array($arguments);
		}

		return call_user_func_array( array($this->_face, $method), $arguments);
	}
}

/*!
 * Face.com Rest API PHP Library v1.0.0 (alpha) 
 * http://face.com/
 *
 * Copyright 2010, 
 * Written By Lior Ben-Kereth
 *  
 * Date: Sun May 02 11:00:48 2010 +0300
 */

define ("API_SERVER", "http://api.face.com/");
define ("API_DEBUG", false);

if (!function_exists('curl_init'))  throw new Exception('Face.com API Client Library requires the CURL PHP extension.');
if (!function_exists('json_decode')) throw new Exception('Face.com API Client Library requires the JSON PHP extension.');

class FaceRestClient
{	
	private $apiKey;
	private $apiSecret;	
	private $password;
	private $format;
	private $http_method;
	private $asRawData;
	
	private $userAuth = array();

	public function __construct($apiKey, $apiSecret, $password = null, $asRawData = false, $format = 'json')
	{
		$this->apiKey    = $apiKey;
		$this->apiSecret = $apiSecret;				
		$this->password  = $password;
		$this->asRawData = $asRawData;

		// When not requesting raw data we always use json, for easier decoding to object
		if (!$asRawData)
			$this->format    = 'json';
	}

	// ***********************
	// Authentication Methods
	// ***********************		
	public function setFBUser($fbUser, $fbSession) 	
	{
		$this->userAuth['fb_user'] = $fbUser;
		$this->userAuth['fb_session'] = $fbSession;		
	}
	
	public function setTwitterUser($twitterUserName, $twitterPassword) 
	{
		$this->userAuth['twitter_username'] = $twitterUserName;
		$this->userAuth['twitter_password'] = $twitterPassword;		
	}
	
	public function setTwitterOAuthUser($twitterOAuthUser, $twitterOAuthToken, $twitterOAuthSecret)
	{
		$this->userAuth['twitter_oauth_user'] = $twitterOAuthUser;
		$this->userAuth['twitter_oauth_token'] = $twitterOAuthToken;		
		$this->userAuth['twitter_oauth_secret'] = $twitterOAuthSecret;				
	}
	
	// *************
	// Account Methods
	// *************
	public function account_authenticate()
	{				
		return $this->call_method("account/authenticate", array());
	}
	
	public function account_limits()
	{				
		return $this->call_method("account/limits", array());
	}	

    public function account_users($namespaces = null)
    {
        list ($namespaces) = $this->prep_lists($namespaces);

        return $this->call_method("account/users", array("namespaces" => $namespaces));
    }
    
	public function account_namespaces()
   	{        
        return $this->call_method("account/namespaces", array());
    }

	
	// *************
	// Faces Methods
	// *************
		
	public function faces_detect($urls = null, $filename = null, $ownerIds = null, $callbackUrl = null, $detector = null)
	{
		list ($urls) = $this->prep_lists($urls, $ownerIds);
			
		return $this->call_method("faces/detect", 
								  array("urls" => $urls ,
								  		"owner_ids" => $ownerIds,
								  		"_file" =>  '@'.$filename,
								  		"callback_url" => $callbackUrl,
								  		"detector" => $detector
								  ));
	}

	public function faces_recognize($urls = null, $uids = null, $namespace = null,  
									$filename = null, $ownerIds = null, $callbackUrl = null,
									$detector = null)
	{
		list ($urls, $uids, $ownerIds) = $this->prep_lists($urls, $uids, $ownerIds);

		return $this->call_method("faces/recognize", 
								  array("urls" => $urls,								  		
								  		"uids" => $uids,								  		
								  		"namespace" 	=> $namespace,								  		
								  		"owner_ids" 	=> $ownerIds,
								  		"_file" =>  '@'.$filename,
								  		"callback_url" => $callbackUrl,
								  		"detector" => $detector
								  ));
	}	
	 
	public function faces_group($urls = null, $uids = null, $namespace = null,  
								$ownerIds = null, $callbackUrl = null,
								$detector = null)
	{
		list ($urls, $uids, $ownerIds) = $this->prep_lists($urls, $uids, $ownerIds);

		return $this->call_method("faces/group", 
								  array("urls" => $urls,								  		
								  		"uids" => $uids,								  		
								  		"namespace" 	=> $namespace,								  		
								  		"owner_ids" 	=> $ownerIds,
								  		"callback_url" => $callbackUrl,
								  		"detector" => $detector
								  ));
	}	
		
	public function faces_train($uids, $namespace = null, $callbackUrl = null)
	{
		list ($uids) = $this->prep_lists($uids);

		return $this->call_method("faces/train", 
								  array("uids" 			 => $uids,								  		
								  		"namespace" 	 => $namespace,
								  		"callback_url" 	 => $callbackUrl								  		
								  ));
	}
	
	public function faces_status($uids, $namespace = null)
	{
		list ($uids) = $this->prep_lists($uids);

		return $this->call_method("faces/status", 
								  array("uids" 			 => $uids,								  		
								  		"namespace" 	 => $namespace								  		
								  ));
	}
	
	// ************
	// Tags Methods
	// ************
	
	public function tags_add($url, $x, $y, $width, $height, $label, $uid = null, $pid = null, $taggerId =null, $ownerId = null)
	{		
		return $this->call_method("tags/add", 
								  array("url" => $url,
								  		"x" => $x,
								  		"y" => $y,
								  		"width" => $width,
								  		"height" => $height,
								  		"label" => $label,
								  		"uid" => $uid,
								  		"pid" => $pid,							  										  										  
								  		"tagger_id" => $taggerId,
								  		"owner_id" => $ownerId,						    
								  ));
	}	
	
	public function tags_save($tids, $uid = null, $label = null, $taggerId = null)
	{		
		list ($tids) = $this->prep_lists($tids);
		
		return $this->call_method("tags/save", 
								  array("tids"	 => $tids,
								  		"label"  => $label,
								  		"uid" 	 => $uid,
								  		"tagger_id" => $taggerId
								  ));
	}
	
	public function tags_remove($tids, $taggerId = null)
	{		
		return $this->call_method("tags/remove", 
								 	 array("tids"	   => $tids,
								 	 	   "tagger_id" => $taggerId								 	 	   								  
								  ));
	}
			
	
	public function tags_get($urls = null, $pids = null, $filename = null, $ownerIds = null, $uids = null, $namespace = null, $filter = null, $limit = null, $together = null, $order = null)
	{
		list ($uids) = $this->prep_lists($uids);

		return $this->call_method("tags/get", 
								  array("urls"		=> $urls,
								  		"pids"	 	=> $pids,								 	 									 	 	  
								 	 	"owner_ids"	=> $ownerIds,
								 	 	"_file"	 	=> '@'.$filename,
								  		"uids" => $uids,
								  		"together" => $together,
								  		"filter" => $filter,
								  		"order" => $order,
								  		"limit" => $limit,
										"namespace" => $namespace
								  ));
	}

	
	// ***************
	// Facebook methods
	// ***************
	public function facebook_get($uids, $filter = null, $limit = null, $together = null, $order = null)
	{
		list ($uids) = $this->prep_lists($uids);

		return $this->call_method("facebook/get", 
								  array("uids" => $uids,
								  		"limit" => $limit,
								  		"together" => $together,
								  		"filter" => $filter,
								  		"order" => $order								  														  										
								  ));
	}
	
	
	// ***************
	// Private methods
	// ***************
	
    protected function call_method($method, $params = array())
    {
    	foreach ($params as $key => $value)
    	{
    		if (empty($value))
    			unset($params[$key]);
    	}
    	
    	// Remove the file param if no filename is there
    	if (isset($params['_file']) && $params['_file'] == "@")
    		unset($params['_file']);
    		
    	$authParams = array();
    		
    	if (!empty($this->apiKey))
    		$authParams['api_key'] = $this->apiKey;
    		
    	if (!empty($this->apiSecret))
    		$authParams['api_secret'] = $this->apiSecret;
    		
    	if (!empty($this->userAuth))
    		$authParams['user_auth'] = $this->getUserAuthString($this->userAuth);    	
    		
    	if (!empty($this->password))
    		$authParams['password'] = $this->password;	
		
    	// Keep th auth keys first
    	$params = array_merge($authParams, $params);    	    	
    	$paramsQS = http_build_query($params);    	    	
    	    
    	$request = "$method.$this->format";
    	
    	return $this->post_request($request, $params);
    }
    
    protected function getUserAuthString($userAuthArray)
    {
    	$string = "";
    
    	if (!empty($userAuthArray))
    	{	    
	    	foreach ($userAuthArray as $key => $value)
	    	{
	    		$string .= "$key:$value,";
	    	}
	    	
	    	$string = substr($string, 0, strlen($string) - 1);
    	}
    	
    	return $string;
    }

	protected function post_request($request, $params)
	{		
		$url = API_SERVER . "$request";
		
		if (API_DEBUG)
		{
			echo "REQUEST: $url?" .http_build_query($params);			
		}		
	
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_POST, 1);    	
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     	$rawData = curl_exec($ch);
    	curl_close($ch);    
    	
    	if ($this->asRawData)
    		return $rawData;
    	else    		
    		return $this->toObject($rawData);    	
	}
		
	protected function prep_lists()
	{
		$result = array();
		foreach (func_get_args() as $arg)
		{
			if (isset($arg))
			{
				if (is_array($arg))
	            	$arg = join(",", $arg);
				$result[] = $arg;
			}
			else
				$result[] = "";
		}

		return $result;
	}
	
	protected function toObject($rawData)
	{
		$result = null;
		
		if (!empty($rawData))
		{
			if ($this->format == 'json')
				$result = json_decode($rawData);
			else
			{
				$sxml = simplexml_load_string($rawData);
				$result = self::convert_simplexml_to_array($sxml);
			}
		}
		
		return $result;
	}
	
	public static function convert_simplexml_to_array($sxml) {
		$arr = array();
		if ($sxml) {
			foreach ($sxml as $k => $v) {
				if ($sxml['list']) {
					$arr[] = self::convert_simplexml_to_array($v);
				} else {
					$arr[$k] = self::convert_simplexml_to_array($v);
				}
			}
		}
		if (sizeof($arr) > 0) {
			return $arr;
		} else {
			return (string)$sxml;
		}
	}
}

/* End of file Face.php */
