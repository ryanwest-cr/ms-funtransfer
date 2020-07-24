<?php

namespace App\Http\Controllers;

//require __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;

use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helpers\TidyHelper;
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
	 // public function conecteccc(Request $request){
	 // 	//return self::decodeToken(array('username' => 'tidyname'));
	 // 	$data = ProviderHelper::auth(
		//  	'/api/user/outside/info', 'GET', array('username' => $request->username, 'client_id' => $request->client_id)
		// );
	 	
	 // 	return $data;
	 // }

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
	 	
			if($data["error_code"] != '00-0370-00-04-002'){

			} else {
				$data_info = array(
					'check' => '0',
					'info' => null
				);
			}
			return $data_info;
			
		}else {
				$errormessage = array(
					'error_code' 	=> '08-025',
					'error_msg'  	=> 'not_found',
					'request_uuid'	=> $request->request_uuid
				);

				return json_encode($errormessage);
		}
		
	 
	 }



	 public function getGamelist(Request $request){

	 	// $data = TidyHelper::auth(
		 // '/api/game/outside/list',  array('username' => $request->username, 'client_id' => $request->client_id)
		 // 	  );
	 	
	 	// return $data;
	 		$url = self::API_URL.'/api/game/outside/list';
	 	   $requesttosend = [
                'username' =>  'tidyname',
                'client_id' => '8440a5b6'
            ];
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
                ]
            ]);
            $guzzle_response = $client->get($url);
            $client_response = json_decode($guzzle_response->getBody()->getContents());

            return json_encode($client_response);
	 }



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

		$data = file_get_contents("php://input");
		$details = json_decode($data);
		
		Helper::saveLog('Tidy Game Bet', 22, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Bet', 22, json_encode($request->all()), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token',$details->token);
		
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);

		$game_details = Helper::findGameDetails('game_code', 22, $details->game_id);

		
		$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$requesttosend = [
				  "access_token" => $client_details->client_access_token,
				  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				  "type" => "fundtransferrequest",
				  "datesent" => Helper::datesent(),
				  "gamedetails" => [
				    "gameid" => $game_details->game_code, // $game_details->game_code
				    "gamename" => $game_details->game_name
				  ],
				  "fundtransferrequest" => [
					  "playerinfo" => [
						"client_player_id" => $client_details->client_player_id,
						"token" => $client_details->player_token,
					  ],
					  "fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => "debit",
						      "transferid" => "",
						      "rollback" => false,
						      "currency" => $client_details->currency,
						      "amount" => abs($details->amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);

		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $status = json_encode($client_response->fundtransferresponse->status->code);	
		    $currency_code = $client_details->default_currency;

		    if($status){
		    	$data_response = [
		    		"uid" 		   => $details->uid,
		    		"request_uuid" => $details->request_uuid,
		    		"currency"	   => $currency_code
		    	];
		    }
		    return $data_response;
	}

	public function gameRollback(Request $request){
		Helper::saveLog('Tidy Game Rollback', 22, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Rollback', 22, json_encode($request->all()), 'ENDPOINT HIT');
	}

	public function gameWin(Request $request){
		Helper::saveLog('Tidy Game Win', 22, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('Tidy Game Win', 22, json_encode($request->all()), 'ENDPOINT HIT');
		
	}
	



}
