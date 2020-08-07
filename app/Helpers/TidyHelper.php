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


	 // const CLIENT_ID = '8440a5b6';
	 // const SECRET_KEY = 'f83c8224b07f96f41ca23b3522c56ef1'; 
	 // const API_URL = 'http://staging-v1-api.tidy.zone';


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
		 $jwt = JWT::encode($data, config('providerlinks.tidygaming.SECRET_KEY'));
		 return $jwt;
	 }

	// JWT VERIFICATION
    public static function decodeToken($token){
		$token = $token;
		try {
			$decoded = JWT::decode($token, config('providerlinks.tidygaming.SECRET_KEY'), array('HS256'));
			// return json_encode($decoded);
			return 'true';
		} catch(\Exception $e) {
			// $response = [
			// 			"errorcode" =>  "authorization_error",
			// 			"errormessage" => "Verification is failed.",
			// 		];
			// return json_encode($response);
			return 'false';
		}
	}

	 public static function currencyCode($currency){
	 	$currency = strtoupper($currency);
	 	$code = '';
	 	switch ($currency) {
	 		case 'CNY':
	 			$code = '156';
	 			break;
	 		case 'THB':
	 			$code = '764';
	 			break;
	 		case 'IDR':
	 			$code = '360';
	 			break;
	 		case 'MYR':
	 			$code = '458';
	 			break;
	 		case 'VND':
	 			$code = '704';
	 			break;
	 		case 'KRW':
	 			$code = '410';
	 			break;
	 		case 'JPY':
	 			$code = '392';
	 			break;
	 		case 'BND':
	 			$code = '096';
	 			break;
	 		case 'HKD':
	 			$code = '344';
	 			break;
	 		case 'SGD':
	 			$code = '702';
	 			break;
	 		case 'PHP':
	 			$code = '608';
	 			break;
	 		case 'TRY':
	 			$code = '949';
	 			break;
	 		case 'USD':
	 			$code = '840';
	 			break;
	 		case 'GBP':
	 			$code = '826';
	 			break;
	 		case 'EUR':
	 			$code = '978';
	 			break;
	 		case 'INR':
	 			$code = '356';
	 			break;
	 		case 'MMK':
	 			$code = '104';
	 			break;
	 		case 'KHR':
	 			$code = '116';
	 			break;
	 		case 'CAD':
	 			$code = '124';
	 			break;
	 		case 'LAK':
	 			$code = '418';
	 			break;
	 		case 'AUD':
	 			$code = '036';
	 			break;
	 		case 'UAH':
	 			$code = '980';
	 			break;
	 		case 'NOK':
	 			$code = '578';
	 			break;
	 		case 'SEK':
	 			$code = '752';
	 			break;
	 		case 'ZAR':
	 			$code = '710';
	 			break;
	 		case 'BDT':
	 			$code = '050';
	 			break;
	 		case 'LKR':
	 			$code = '144';
	 			break;
	 		case 'RUB':
	 			$code = '643';
	 			break;
	 		case 'PLN':
	 			$code = '985';
	 			break;
	 		case 'AED':
	 			$code = '784';
	 			break;
	 		case 'BRL':
	 			$code = '986';
	 			break;
	 		case 'CHF':
	 			$code = '756';
	 			break;
	 		case 'NZD':
	 			$code = '554';
	 			break;
	 		case 'HUF':
	 			$code = '348';
	 			break;
	 		case 'DKK':
	 			$code = '208';
	 			break;
	 	}

	 	return $code;
	 }

}