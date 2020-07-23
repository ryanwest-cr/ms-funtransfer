<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use DB; 


/**
 * DEPRECATED
 */
class PlayTeach{

	public static $falcon_api = 'api.operatorapi.com/fun/games/{gameCode}';
	public static $admin_kiosk = 'kiosk.pt-bd88.com';
    public static $api_url = 'https://api.gcpstg.m27613.com';
    public static $seamless_key = '47138d18-6b46-4bd4-8ae1-482776ccb82d';
    public static $seamless_username = 'TGAMESU_USER';
    public static $seamless_password = 'Tgames1234';
    public static $merchant_data = 'TIGERGAMESU';
    public static $merchant_password = 'LmJfpioowcD8gspb';

    // TEST
	public static  function makeCall(){
		$path = dirname(__FILE__).'\SkyWind\\';
		// return $path.'CNY_UAT_FB88.pem';
		$url= "https://api.gcpstg.m27613.com//games/info/search";
		$entity_key= "3cf272869754310eca13e63f53f333c241874bd4736e69f90b647008b0fb4f843e81bea5e8f378aed6cff881699fb85011eaa2cc8cc8f12c1c2126de609df5692";
		$header   = array();
		$header[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive:timeout=5, max=100";
		$header[] = "Accept-Charset:ISO-8859-1,utf-8;q=0.7,*;q=0.3";
		$header[] = "Accept-Language:es-ES,es;q=0.8";
		$header[] = "Pragma: ";
		$header[] = "X_ENTITY_KEY: " . $entity_key;
		
		$tuCurl= curl_init();
		curl_setopt($tuCurl, CURLOPT_URL, $url);
		curl_setopt($tuCurl, CURLOPT_PORT , 443);
		curl_setopt($tuCurl, CURLOPT_VERBOSE, 0);
		curl_setopt($tuCurl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($tuCurl, CURLOPT_TIMEOUT, 60000 );
		curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($tuCurl, CURLOPT_SSLCERT, $path . 'JPY.pem');
		// curl_setopt($tuCurl, CURLOPT_SSLCERT, $path . 'CNY_UAT_FB88.pem');
		// curl_setopt($tuCurl, CURLOPT_SSLCERT, $path . '</api/ssl.pem>');
		curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($tuCurl, CURLOPT_SSLKEY, $path . 'JPY.key');
		// curl_setopt($tuCurl, CURLOPT_SSLKEY, $path . 'CNY_UAT_FB88.key');
		// curl_setopt($tuCurl, CURLOPT_SSLKEY, $path . '</api/ssl.key>');
			
		$exec = curl_exec($tuCurl);
		// curl_close($tuCurl);

		$httpcode = curl_getinfo($tuCurl, CURLINFO_HTTP_CODE);
	    if($httpcode < 200 || $httpcode >= 300)
	    {
	        $status = 0;
	        $error = $httpcode;
	    }
	    if(curl_errno($tuCurl))
	    {
	        $error = curl_error($tuCurl);
	        $status = 0;
	    }

		return array($status, $exec, $error);
		// $data = json_decode($exec, TRUE);

		// echo"<pre>";
		// print_r($data);
		// echo"</pre>";

	}

}