<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;

use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helpers\TidyHelper;
use DB;


class TidyController extends Controller
{
	 public $prefix_id = 'TG';
	 public $provider_db_id = 23;// change local= 22 || online = 23
	 public $client_id = '8440a5b6';
	 public $API_URL = 'http://staging-v1-api.tidy.zone';
	 // const SECRET_KEY = 'f83c8224b07f96f41ca23b3522c56ef1'; // token


	 //wla pani nahuman
	 public function autPlayer(Request $request){
	 	$playersid = explode('_', $request->username);
		$getClientDetails = ProviderHelper::getClientDetails('player_id',$playersid[1]);
		if($getClientDetails != null){
			$getPlayer = ProviderHelper::playerDetailsCall($getClientDetails->player_token);
			$get_code_currency = TidyHelper::currencyCode($getClientDetails->default_currency);
			$data_info = array(
				'check' => '1',
				'info' => [
					'username' => $getClientDetails->username,
					'nickname' => $getClientDetails->display_name,
					'currency' => $get_code_currency,	
					'enable'   => 1,
					'created_at' => $getClientDetails->created_at
				]
			);
			return response($data_info,200)->header('Content-Type', 'application/json');
		}else {
			$errormessage = array(
				'error_code' 	=> '08-025',
				'error_msg'  	=> 'not_found',
				'request_uuid'	=> $request->request_uuid
			);

			return response($errormessage,200)->header('Content-Type', 'application/json');
		}
	 }


	// One time usage
	public function getGamelist(Request $request){
 		$url = $this->API_URL.'/api/game/outside/list';
 	    $requesttosend = [
            'client_id' => $this->client_id
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

	 // TEST
 	public function demoUrl(Request $request){
			$url = $this->API_URL.'/api/game/outside/demo/link';
	 	    $requesttosend = [
                'client_id' => $this->client_id,
                'game_id'	=> 1,
                'back_url'  => 'http://localhost:9090',
                'quality'	=> 'MD',
                'lang'		=> 'en'
            ];
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
                ]
            ]);
            $guzzle_response = $client->post($url);
            $client_response = json_decode($guzzle_response->getBody()->getContents());

            return $client_response;
	}


	/* SEAMLESS METHODS */
	public function checkBalance(Request $request){
		//Helper::saveLog('Tidy Check Balance', 23, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT v2');
		//$data = json_decode(file_get_contents("php://input")); // INCASE RAW JSON / CHANGE IF NOT ARRAY
		$header = $request->header('Authorization');
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
	    // Helper::saveLog('Tidy Bal 1', 23, json_encode(file_get_contents("php://input")), $data);
	   
		$token = $data->token;
		$request_uuid = $data->request_uuid;
		$client_details = ProviderHelper::getClientDetails('token',$token);
		if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				//$balance = number_format($player_details->playerdetailsresponse->balance, 2); 
				$currency = $client_details->default_currency;
				$get_code_currency = TidyHelper::currencyCode($currency);

				$num = $player_details->playerdetailsresponse->balance;
				$balance = (double)$num;
				$data =  array(	
		 			 "uid"			=> $this->prefix_id.'_'.$client_details->player_id,
					 "request_uuid" => $request_uuid,
					 "currency"		=> $get_code_currency,
					 "balance" 		=> $balance
			 	);
				Helper::saveLog('Tidy Check Balance Response', $this->provider_db_id, json_encode($request->all()), $data);
				// return response($data,200)->header('Content-Type', 'application/json');
				return $data;
		}else{
			$errormessage = array(
				'error_code' 	=> '08-025',
				'error_msg'  	=> 'not_found',
				'request_uuid'	=> $request_uuid
			);
			return $errormessage;
		}
	}

	public function gameBet(Request $request){
		//$data = json_decode(file_get_contents("php://input"));
		// Helper::saveLog('Tidy Game Bet', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$header = $request->header('Authorization');
	    Helper::saveLog('Tidy Authorization Logger BET', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

	    $enc_body = file_get_contents("php://input");
     	parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

		$game_id = $data->game_id;
		$token = $data->token;
		$amount = $data->amount;
		$uid = $data->uid;
		$request_uuid = $data->request_uuid;
		$transaction_uuid = $data->transaction_uuid;

		$client_details = ProviderHelper::getClientDetails('token',$token); // cheking the token and get details
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);

		$transaction_check = ProviderHelper::findGameExt($transaction_uuid, 1,'transaction_id');

		if($transaction_check != 'false'){
			$data_response = [
				'error' => '99-011' 
			];
			return $data_response;
		}

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
					      "currency" => $client_details->default_currency,
					      "amount" => abs($amount)
				   ],
			  ],
		];
		$guzzle_response = $client->post($client_details->fund_transfer_url,
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());

	    $transaction_type = 'debit';
		$game_transaction_type = 1; // 1 Bet, 2 Win
		$game_code = $game_details->game_id;
		$token_id = $client_details->token_id;

		$bet_amount = abs($amount);

		$pay_amount = 0;
		$income = 0;
		$win_type = 0;
		$method = 1;
		$win_or_lost = 5; // 0 lost,  5 processing
		$payout_reason = 'Bet';
		$provider_trans_id = $transaction_uuid;

		$num = $client_response->fundtransferresponse->balance;
		$balance = (double)$num;

		$data_response = [
    		"uid" => $uid,
    		"request_uuid" => $request_uuid,
    		"currency" => TidyHelper::currencyCode($client_details->default_currency),
    		"balance" =>  $balance
    	];

	    $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
	    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $provider_trans_id, $pay_amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);

	    Helper::saveLog('Tidy Bet Processed', $this->provider_db_id, json_encode(file_get_contents("php://input")), $data_response);
	   
	    return $data_response;
	}

	public function gameWin(Request $request){
		//$data = json_decode(file_get_contents("php://input"));
		// Helper::saveLog('Tidy Game WIN', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$header = $request->header('Authorization');
    	Helper::saveLog('Tidy Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

	    $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

		$game_id = $data->game_id;
		$token = $data->token;
		$amount = $data->amount;
		$uid = $data->uid;
		$request_uuid = $data->request_uuid;
		$transaction_uuid = $data->transaction_uuid; // MW - provider identifier 
		$reference_transaction_uuid = $data->reference_transaction_uuid; //  MW - round id

		$client_details = ProviderHelper::getClientDetails('token',$token);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);

		$existing_bet = ProviderHelper::findGameExt($reference_transaction_uuid, 1,'transaction_id');
		
		if($existing_bet == 'false'){
			return "no record found";
		}

		$transaction_check = ProviderHelper::findGameExt($transaction_uuid, 2,'transaction_id');

		if($transaction_check != 'false'){
			$data_response = [
				'error' => '99-011' 
			];
			return $data_response;
		}

		$bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');

			
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
						      "transactiontype" => "credit",
						      "transferid" => "",
						      "rollback" => false,
						      "currency" => $client_details->default_currency,
						      "amount" => abs($amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    // $status = json_encode($client_response->fundtransferresponse->status->code);	

		    $num = $client_response->fundtransferresponse->balance;
			$balance = (double)$num;

			$data_response = [
	    		"uid" => $uid,
	    		"request_uuid" => $request_uuid,
	    		"currency" => TidyHelper::currencyCode($client_details->default_currency),
	    		"balance" => $balance
	    	];

	    	$round_id = $reference_transaction_uuid;
	    	$amount = $amount ;
	    	$pay_amount = $amount;
	    	$income = $bet_transaction->bet_amount - $amount ;
	    	// $win = 1;
	    	$entry_id = $amount > 0  ?  2 : 1;// [<1 bet/debit, 2 win/credit>]
	    	$win 	  = $amount > 0  ?  1 : 0;  /// 1win 0lost

	    	ProviderHelper::updateBetTransaction($round_id, $amount, $income, $win, $entry_id);
		    $game_transextension = ProviderHelper::createGameTransExt($existing_bet->game_trans_id,$request_uuid,$reference_transaction_uuid, $amount, 2, $data, $data_response, $requesttosend, $client_response, $data_response);

		    Helper::saveLog('Tidy Win Processed', $this->provider_db_id, json_encode(file_get_contents("php://input")), $data_response);
	        // return response($data_response,200)->header('Content-Type', 'application/json');
	        return $data_response;
	}


	public function gameRollback(Request $request){
		//$data = json_decode(file_get_contents("php://input"));
		// Helper::saveLog('Tidy Game Rollback', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$header = $request->header('Authorization');
	    // Helper::saveLog('Tidy Authorization Logger', $this->provider_db_id, file_get_contents("php://input"), $header);
	    Helper::saveLog('Tidy Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

	    $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

		$game_id = $data->game_id;
		$uid = $data->username;
		$token = $data->token;
		$request_uuid = $data->request_uuid;
		$transaction_uuid = $data->transaction_uuid; // MW - provider identifier 
		$reference_transaction_uuid = $data->reference_transaction_uuid; //  MW - round id

		$client_details = ProviderHelper::getClientDetails('token',$token);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);

		$existing_bet = ProviderHelper::findGameExt($reference_transaction_uuid, 1,'transaction_id');

		
		if($existing_bet == 'false'){
			$data_response = [
				'error' => '99-012' // 99-012 transaction_does_not_exist
			];
			return $data_response;
		}
		$refund_call = ProviderHelper::findGameExt($transaction_uuid, 3,'transaction_id');
		if($refund_call != 'false'){
			$data_response = [
				'error' => '99-013' // transaction rolledback
			];
			return $data_response;
		}

		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    	'Authorization' => 'Bearer '.$client_details->client_access_token
		    ]
		]);

		$bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');

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
					      "transactiontype" => "credit",
					      "transferid" => "",
					      "rollback" => true,
					      "currency" => $client_details->default_currency,
					      "amount" => $existing_bet->amount
				   ],
			  ],
		];
		$guzzle_response = $client->post($client_details->fund_transfer_url,
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());

	

    	$round_id = $reference_transaction_uuid;
	    $win = 4;
	    $entry_id = 1;
	    $data_response = [
    		"uid" => $uid,
    		"request_uuid" => $request_uuid,
    		"currency" => TidyHelper::currencyCode($client_details->default_currency),
    		"balance" => $client_response->fundtransferresponse->balance 
    	];


    	$game_transextension = ProviderHelper::createGameTransExt($existing_bet->game_trans_id,$transaction_uuid,$reference_transaction_uuid, $bet_transaction->bet_amount, 3, $data, $data_response, $requesttosend, $client_response, $data_response);
    	$game_update_refound = $this->rollbackTransaction($round_id, $win, $entry_id);
    	Helper::saveLog('Tidy Rollback Processed', $this->provider_db_id, json_encode(file_get_contents("php://input")), $data_response);

	    return $data_response;

	}


	public  static function rollbackTransaction($round_id,$win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('round_id', $round_id)
                ->update([
                	  'bet_amount' => 0,
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
		return ($update ? true : false);
	}



}

	