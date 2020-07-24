<?php
namespace App\Helpers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use Firebase\JWT\JWT;

use DB;

class TidyHelper{


	 const CLIENT_ID = '8440a5b6';
	 const SECRET_KEY = 'f83c8224b07f96f41ca23b3522c56ef1'; 
	 const API_URL = 'http://staging-v1-api.tidy.zone';


	// //for the tidy
	//   public static function auth($uri,  Array $data = []) {
	// 	 // $curl = new Curl();
	// 	 // $data['client_id'] = self::CLIENT_ID;
	// 	 // $curl->setHeader(
	// 	 // 		'Authorization' ,'Bearer ' . self::generateToken($data),
	// 	 // 		'Accept', 'application/json'
	// 	 // );


	// 	 // $method = strtolower($method);
	// 	 // $curl->{$method}(self::API_URL . $uri, $data);
	// 	 // return json_decode($curl->response, true);

 //            $client = new Client([
 //                'headers' => [ 
 //                    'Content-Type' => 'application/json',
 //                    'Authorization' => 'Bearer '.TidyHelper::generateToken($data)
 //                ]
 //            ]);
 //            $guzzle_response = $client->post(self::API_URL . $uri,['body' => json_encode($data)]
 //            );

 //            $client_response = json_decode($guzzle_response->getBody()->getContents());

 //            return $client_response;

	//  }

	  public static function generateToken(Array $data) {
		 $data['iat'] = (int)microtime(true);
		 $jwt = JWT::encode($data, self::SECRET_KEY);
		 return $jwt;
	 }

	// JWT VERIFICATION
	  public static function decodeToken(Array $data){//array('username' => 'tidyname')
		
		$token = self::generateToken($data);
		try {
			$decoded = JWT::decode($token, self::SECRET_KEY, array('HS256'));
			return json_encode($decoded);
		} catch(Exception $e) {
			$response = [
						"errorcode" =>  "authorization_error",
						"errormessage" => "Verification is failed.",
					];


			return json_encode($response);
		}
		
	 }

}