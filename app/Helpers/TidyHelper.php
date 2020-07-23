<?php
namespace App\Helpers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;

use Firebase\JWT\JWT;
// use Curl\Curl;//

use DB;

class TidyHelper{


	 const CLIENT_ID = '8440a5b6';
	 const SECRET_KEY = 'f83c8224b07f96f41ca23b3522c56ef1'; 
	 const API_URL = 'http://staging-v1-api.tidy.zone';

	 //  public static function auth($uri, $method = 'GET', Array $data = []) {
		//  $curl = new Curl();
		//  $data['client_id'] = self::CLIENT_ID;
		//  $curl->setHeader(
		//  		'Authorization' ,'Bearer ' . self::generateToken($data),
		//  		'Accept', 'application/json'
		//  );


		//  $method = strtolower($method);
		//  $curl->{$method}(self::API_URL . $uri, $data);
		//  return json_decode($curl->response, true);

	 // }

	  public static function generateToken(Array $data) {
		 $data['iat'] = (int)microtime(true);
		 $jwt = JWT::encode($data, self::SECRET_KEY);
		 return $jwt;
	 }


}