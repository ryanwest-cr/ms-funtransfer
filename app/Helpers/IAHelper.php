<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use DB; 

class IAHelper{

	// public static $prefix = 'BETRNK';
	// public static $auth_key = '54bc08c471ae3d656e43735e6ffc9bb6';
	// public static $pch = 'BRNK';
	// public static $iv = '45b80556382b48e5';
	// public static $url_lunch = 'http://api.ilustretest.com/user/lunch';
	// public static $url_register = 'http://api.ilustretest.com/user/register';
	// self::$pch

	public static function userlunch($username)
    {
        $params = [
            "username" => $username,
            "lang" => 2, // Default English
            // "client" => 2,  // 2 for wap, 1 for PC
        ];
        $uhayuu = IAHelper::hashen($params);
        $header = ['pch:'. config('providerlinks.iagaming.pch')];
        $timeout = 5;
        $client_response = IAHelper::curlData(config('providerlinks.iagaming.url_lunch'), $uhayuu, $header, $timeout);
        $data = json_decode(IAHelper::rehashen($client_response[1], true));
        return $data->data->url;
    }


    /**
	 * Create Hash Key
	 * @return Encrypted AES string
	 *
	 */
    public static function hashen($params=[])
	{
		$params['auth_key'] = IAHelper::getMD5ParamsString($params);
		$plaintext = json_encode($params);
		$iv = config('providerlinks.iagaming.iv');
		$method = 'AES-256-CBC';
		$hashen = base64_encode(openssl_encrypt($plaintext, $method, config('providerlinks.iagaming.auth_key'), OPENSSL_RAW_DATA, $iv));
		return $hashen;
	}

	/**
	 * Decode Hashen
	 * @return Decoded Hashen AES string
	 *
	 */
	public static function rehashen($hashen)
	{
		$method = 'AES-256-CBC';
		$iv = config('providerlinks.iagaming.iv');
		$rehashen = openssl_decrypt(base64_decode($hashen), $method,config('providerlinks.iagaming.auth_key'), OPENSSL_RAW_DATA, $iv);
		return $rehashen;
	}

    /**
	 * Decode Hashen
	 * @return Sorted Array Keys
	 *
	 */
    public static function getMD5ParamsString($params=[])
    {
        ksort($params);
        $arr = [];
        foreach($params as $key => $val)
        {
            $arr[] = $key . '=' . $val;
        }
        return md5(join(',', $arr));
    }


    /**
	 * Api Call
	 * 
	 * @param postData = encoded string using mcrypt
	 * @param header = header parameters
	 * @return ereturn array($status, $handles, $error)
	 * 
	 */
	public static function curlData($url, $postData = array(), $header = false, $timeout = 10)
	{
	    $error = '';
	    $status = 1;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    if(!empty($header))
	    {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if(!empty($postData))
	    {
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    }
	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	    $handles = curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    if($httpcode < 200 || $httpcode >= 300)
	    {
	        $status = 0;
	        $error = $httpcode;
	    }
	    if(curl_errno($ch))
	    {
	        $error = curl_error($ch);
	        $status = 0;
	    }
	    
	    curl_close($ch);
	    
	    return array($status, $handles, $error);
	}

}