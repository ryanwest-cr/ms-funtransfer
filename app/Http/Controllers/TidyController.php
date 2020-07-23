<?php

namespace App\Http\Controllers;

//require __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
// use App\Helpers\GameSubscription;
// use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use \Firebase\JWT\JWT;
use \Curl\Curl;

use DB;


class TidyController extends Controller
{
	 const CLIENT_ID = '8440a5b6';
	 const SECRET_KEY = 'f83c8224b07f96f41ca23b3522c56ef1'; // token
	 const API_URL = 'http://staging-v1-api.tidy.zone';

	// public function __construct(){
 //    	$this->api_url = config('providerlinks.aws.api_url');
 //    	$this->merchant_id = config('providerlinks.aws.merchant_id');
 //    	$this->merchant_key = config('providerlinks.aws.merchant_key');
 //    }
	 public function conecteccc(Request $request){
	 	//return self::decodeToken(array('username' => 'tidyname'));
	 	$data = self::auth(
		 	'/api/user/outside/info', 'GET', array('username' => $request->username, 'client_id' => $request->client_id)
		);
	 	
	 	return $data;
	 }

	 //wla pani nahuman
	 public function autPlayer(Request $request){

	 	$playersid = explode('_', $request->username);
	 	//checking the userin
		$getClientDetails = ProviderHelper::getClientDetails('player_id',$playersid[1]);
		
		if($getClientDetails){

			$getPlayer = ProviderHelper::playerDetailsCall($getClientDetails->player_token);
			$data_info = array(
				'check' => '1',
				'info' => [
					'username' => $getClientDetails->username,
					'nickname' => $getClientDetails->display_name,
					'currency' => $getPlayer->playerdetailsresponse->currencycode,	
					'enable'   => $getPlayer->playerdetailsresponse->status->status,
					'created_at' => $getClientDetails->created_at
				]
			);

			return json_encode($data_info);
			
		}else {
				$errormessage = array(
					'error_code' 	=> '08-025',
					'error_msg'  	=> 'not_found',
					'request_uuid'	=> $request->request_uuid
				);

				return json_encode($errormessage);
		}
		
	 
	 }


	 public function auth($uri, $method = 'GET', Array $data = []) {
		 $curl = new Curl();
		 $data['client_id'] = self::CLIENT_ID;
		 $curl->setHeader(
		 		'Authorization' ,'Bearer ' . self::generateToken($data),
		 		'Accept', 'application/json'
		 );


		 $method = strtolower($method);
		 $curl->{$method}(self::API_URL . $uri, $data);
		 return json_decode($curl->response, true);
	 }

	 public function generateToken(Array $data) {
		 $data['iat'] = (int)microtime(true);
		 $jwt = JWT::encode($data, self::SECRET_KEY);
		 return $jwt;
	 }

	 // JWT VERIFICATION
	 public function decodeToken(Array $data){//array('username' => 'tidyname')
		
		$token = self::generateToken($data);
		try {
			$decoded = JWT::decode($token, self::SECRET_KEY, array('HS256'));
			return json_encode($decoded);
		} catch(Exception $e) {
			$response = [
						"errorcode" =>  "authorization_error",
						"errormessage" => "Verification is failed.",
					];
		}
		
	 }

	 public function getGamelist(Request $request){

	 	$data = self::auth(
		 '/api/game/outside/list', 'GET', array('username' => $request->username, 'client_id' => $request->client_id)
		 	  );
	 	
	 	return $data;
	 // 	$curl = new Curl();
	 // 	$method = 'GET'; $uri = '/api/game/outside/list';
		// $data['client_id'] = $request->client_id;

		// $curl->setHeader(
		//  		'Authorization' ,'Bearer ' . self::generateToken($data),
		//  		'Accept', 'application/json'
		//  );


		//  $method = strtolower($method);
		//  $curl->{$method}(self::API_URL . $uri, $data);
		//  return json_decode($curl->response, true);

	 }

	 //  public function gameUrl(Request $request){

		// 	$requesttosend = [
		// 			'client_id' =>  '8440a5b6',
		// 		    'game_id' => '',
		// 		    'username' => '',
		// 			'token' => '',
		// 			'uid' => ''
		// 			];
			
		//     $data = self::auth(
		//  		'/api/game/outside/link', 'POST', $requesttosend
		//  	  );
		//     return $data;
	 // }


	 /* SEAMLESS METHODS */
	public function checkBalance(Request $request){
			Helper::saveLog('Tidy Check Balance', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
			$client_details = ProviderHelper::getClientDetails('token',$request->token);
			if($client_details){
				if($request->client_id != null || $request->client_id == self::CLIENT_ID){
					$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
					$data =  array(
					 		'user' => array(
					 			 "uid"			=> $client_details->player_id,
								 "request_uuid" => $request->request_uuid,
								 "currency"		=> $client_details->default_currency,
								 "balance" 		=> $player_details->playerdetailsresponse->balance )
					 );
					return $data;
			}else{
				$errormessage = array(
					'error_code' 	=> '08-025',
					'error_msg'  	=> 'not_found',
					'request_uuid'	=> $request->request_uuid
				);

				return json_encode($errormessage);
			}
		}
	}


	public function gameBet(Request $request){
		Helper::saveLog('Tidy Game Bet', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Bet', 21, json_encode($request->all()), 'ENDPOINT HIT');
	}

	public function gameRollback(Request $request){
		Helper::saveLog('Tidy Game Rollback', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Rollback', 21, json_encode($request->all()), 'ENDPOINT HIT');
	}

	public function gameWin(Request $request){
		Helper::saveLog('Tidy Game Win', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Win', 21, json_encode($request->all()), 'ENDPOINT HIT');
		
	}
	



}
