<?php

namespace FreeVoyo;

class Main
{

	include 'TenMinuteMail.php';

	//this should be in a config file, but who gives a crap..
	private static $checkuser_url 		= "http://voyo.si/bin/registration2/?action=check-username";
	private static $checkuser_success 	= "success_step_1_user_name_ok";
	private static $userprefix 			= "marko_";
	private static $password 			= "janeznovak";
	private static $register_url 		= "http://voyo.si/bin/registration2/?action=update&what=1&return=http://voyo.si/";
	private static $register_success	= "success_step_1_update_ok";
	private static $sms_url 			= "http://voyo.si/lbin/billing/sms.php?type=trial&mailing_id=&mailing_uid=&rand=";
	//private static $phone_numbers		= 
	//private static $mojmobitel_pass		= "gobar123";
	//private static $tv_code				= "F7P5A"; //I believe this stays the same, it's some sort of UDID

	private $url = null;
	private $conn = null;
	private $cookies = null;
	private $tenminmail = null;

	public function __construct()
	{
		$this->conn = curl_init();

		$opts = array( 
			CURLOPT_URL				=> $this->url,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_HEADER			=> true,
			CURLINFO_HEADER_OUT		=> true,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_COOKIESESSION	=> true,
			CURLOPT_COOKIEFILE		=> './jar',
			CURLOPT_COOKIEJAR		=> './jar',
			CURLOPT_COOKIE			=> $this->cookies
		);
		
		curl_setopt_array($this->conn, $opts);
	}

	private static function registerNewAccount()
	{
		$username = self::$userprefix . base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36); //fast random username - maybe move this into own function to avoid repetition?
		
		while(!self::checkUsername())	//retry (just in case)
		{
			$username = self::$userprefix . base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36); 
		}
		$email = self::getEmailName();

		//great, now post it with curl
		$postvars = 'username=' . $username . '&password=' self::$password . '&password_repeat=' . self::$password . '&sex=M&email_r=' . urlencode($email) . '&bday_day=1&bday_month=1&bday_year=' . mt_rand(1910, 1990);
		curl_setopt($this->conn, CURLOPT_URL, self::$register_url);
		curl_setopt($this->conn, CURLOPT_POST, 1);
		curl_setopt($this->conn, CURLOPT_POSTFIELDS, $postvars);
		$response = curl_exec($this->conn);
		if(strpos($response, self::$register_success))
		{
			//ok, we have registered, reply to the mail now
			$emails = $this->tenminmail->getEmails();
			preg_match('#(?<=\>)(www\.|https?:\/\/){1}[a-zA-Z0-9]{2,}\.[a-zA-Z0-9]{2,}(\S*)#i', $emails[0], $matches);
			curl_setopt($this->conn, CURLOPT_URL, $matches[0]);
			$response = curl_exec($this->conn);
			$http_code = curl_getinfo($this->conn, CURLINFO_HTTP_CODE);
			if($httpCode !== 302) 
			{
				throw new Exception("Voyo seems to be having problems.");
				return false;
			}
		//okay, we are successfully registered, save the data to memcachedb
			$cache = new Memcache;
			$cache->connect('localhost', 11211) or throw new Exception("Damn, MemcacheDB is offline.");
			$memcache->set('active_username', $username);
			$memcache->set('active_email', $email);
			$memcache->set('active_time', time());
			return true;

	}

	private static function getEmailName()
	{
		$this->tenminmail = new \TenMinuteMail\Service();
		$this->tenminmail->getNewAddress();
		return $this->tenminmail->getAddress();
	}

	private static function checkUsername($username)
	{
		$postvars = 'what=' . $username;
		curl_setopt($this->conn, CURLOPT_URL, self::$checkuser_url);
		curl_setopt($this->conn, CURLOPT_POST, 1);
		curl_setopt($this->conn, CURLOPT_POSTFIELDS, $postvars);
		$response = curl_exec($this->conn);
		
		if(strpos($response, self::$checkuser_success))
		{
			return true;
		}
		return false;

	}

	private static function sendTrialSMS()
	{
		//keep same curl connection so that we have teh cookiez
		curl_setopt($this->conn, CURLOPT_URL, self::$sms_url . mt_rand(100000000000, 999999999999);
		$smscode = curl_exec($this->conn); //SMS code
		/*$cl = new SoapClient("https://moj.mobitel.si/mobidesktop-v2/wsdl.xml");
		$cl->SendSMS( //wrap this in try/catch blocks
    		array(
    		"Username" => self::$phone_number,
    		"Password" => self::$mojmobitel_pass,
    		"Recipients" => array("3232"),
    		"Message" => 'VOYO' . $smscode
    		)
		); okay, that ain't that dumb :D */
		//fix this until next revision
		//there will be 2 options, either an array of phone numbers (from other people, friends) and their mojmobitel pwd's
		//or, and this is more likely, I'll write a simple PHP wrapper extension for OsmocomBB, then use the techniques described here: http://slo-tech.com/clanki/12003/
		//this will require a monitoring station, which will check what's on it's and neighbour AP's, but that's not an issue
		//I've been meaning to write a PHP extension for a while now, also check out phalconPHP, it's great!
		echo "Message sent!";



	}

	private static function registerTV()
	{
		//send register TV sms
	}


	private static function sendDeleteSMS()
	{
		//send delete sms
	}

	public function check()
	{
		//check if less than 24hours until trial end, then send delete sms and create new account
	}



}

//don't run this, it's a work in progress.
die();