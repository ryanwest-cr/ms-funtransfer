<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class TGGController extends Controller
{
     public $project_id = 1421;
	 public $api_key = '29abd3790d0a5acd532194c5104171c8';
	 public $api_url = 'http://api.flexcontentprovider.com';
	 public $provider_db_id = 22; // this is not final provider no register local

	/**
	* $system_id - your project ID (number)
	* $version - API version (number)for API request OR callback version (number) for callback call
	* $args - array with API method OR callback parameters. API method parameters list you can find in the API method description
	* $system_key - your API key (secret key)
	*/ 
	public static function  getSignature($system_id, $version, array $args, $system_key){
		$md5 = array();
		$md5[] = $system_id;
		$md5[] = $version;

		$signature = $args['signature']; // store the signature
		unset($args['signature']); // remove signature from the array
		
		foreach ($args as $required_arg) {
			$arg = $required_arg;
			if(is_array($arg)){
				if(count($arg)) {
					$recursive_arg = '';
					array_walk_recursive($arg, function($item) use (& $recursive_arg) {
						if(!is_array($item)) { $recursive_arg .= ($item . ':');} 
					});
					$md5[] = substr($recursive_arg, 0, strlen($recursive_arg)-1); // get rid of last
				} else {
					$md5[] = '';
				}
			} else {
				$md5[] = $arg;
			}
		};

		$md5[] = $system_key;
		$md5_str = implode('*', $md5);
		$md5 = md5($md5_str);
		if($md5 == $signature){  // Generate Hash And Check it also!
	    	return true;
	    }else{
	    	return false;
	    }
	}

	public function getGamelist(Request $request){
		$data = [
			'need_extra_data' => 1
		];
		$signature =  $this->getSignature($this->project_id,$this->version,$data,$this->api_key);
		$url = $this->api_url.'/game/getlist';
        $requesttosend = [
            'project' =>  $this->project_id,
			'version' => $this->version,
			'signature' => $signature,
			'need_extra_data' => 1
		];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$signature
            ]
        ]);
        $guzzle_response = $client->post($url,['body' => json_encode($requesttosend)]);
		$client_response = json_decode($guzzle_response->getBody()->getContents());
        return json_encode($client_response);
	}


	public function getURL(){
		$data = [
			'project'=> 113, //int
			'version'=> 1, //int
			'signature'=> 'f41979c1fe708313c66acada53f913d1',//string 32
			'token'=> 'j45hg67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57',//string 49
			'game'=> 'fullstate\html5\ugproduction\luckylimo',//string 34
			'settings'=> [
				'user_id'=> 'testuserG407',//string 12
				'exit_url'=> 'https://google.com?test=1&test2=2#exit_url',//string 43
				'cash_url'=> 'https://google.com?test=1&test2=2#cash_url',//string 43
				'language'=> 'en',//string 2
				'denominations'=> [
					0 => 0.01,//float
					1 => 0.1,//float
					2 => 0.25,//float
					3 => 1,//int
					4 => 10,//int
				],
				'https'=> 1,//int
			],
			'denomination'=> 1,
			'currency'=> 'USD',//string 3
			'return_url_info'=> 1,//int
			'callback_version'=> 2//int
		];
		return $data;
	}
	/**
	 * Initialize the balance 
	 */
	public function gameInit(Request $request){
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
		$data = json_decode($json_encode);
		//Helper::saveLog('TGG index '.$request->name, $this->provider_db_id, json_encode($data), 'ENDPOINT HIT');
		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key);
		if(!$signature_checker):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			//Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($data), $msg);
			return $msg;
		endif;
		
		$token = $data->token;
		$client_details = ProviderHelper::getClientDetails('token',$token);
		if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$data_response = [
					'status' => 'ok',
					'data' => [
						'balance' => $player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
						'display_name' => $client_details->display_name
					]
				];
				//Helper::saveLog('Tidy Check Balance Response', $this->provider_db_id, json_encode($request->all()), $data);
				return $data_response;
		}else{
			$data_response = [
				'status' => 'error',
				'error' => [
					'scope' => "user",
					'message' => "not found",
					'detils' => ''
				]
			];
			return $data_response;
		}
	}

	public function gameBet(Request $request){
		// $enc_body = file_get_contents("php://input");
		// parse_str($enc_body, $data);
		// $json_encode = json_encode($data, true);
		// $data = json_decode($json_encode);
		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key);
		if(!$signature_checker):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			//Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($data), $msg);
			return $msg;
		endif;
		$request = [
			'token' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
			'callback_id' => 'llngrl0xem8cf',
			'name' => 'bet',
			'data' => [
				'round_id' => '92611e1e06d1fcc516358a978002caaf6a82d9ec9c42703a',
				'action_id' => 'fe18c9b6550c0afc939eb311f423b207d6b2377c74ad2638',
				'amount' => 2.5,
				'currency' => 'USD',
				'details' => [
					'game' => [
						'game_id' => 981,
						'absolute_name' => 'fullstate\\html5\\ugproduction\\luckylimo',
					],
					'currency_rate' => [
						'currency' => 'USD',
						'rate' => 1,
					],
					'bet' => 1,
					'lines' => 2.5,
					'balance_before_pay' => 9992.5000,
					'pay_for_action_this_round' => 2.5,
				],
			],
			'signature' => '2142a983ffe7fedcc26cd765a32df880'
		];
	
		
		$game_ext = $this->findGameExt($request['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension
	
		if($game_ext == 'false'): // NO BET
			//Helper::saveLog('TGG Authorization Logger BET', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $request["data"]["details"]["game"]["game_id"]);	
			
			$player_details = ProviderHelper::playerDetailsCall($request['token']);
			//if the amount is grater than to the bet amount  error message
			if($player_details->playerdetailsresponse->balance < $request['data']['amount']):
				$msg = array(
					"status" => 'error',
					"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
				);
				return $msg;
			endif;

			$client_details = ProviderHelper::getClientDetails('token', $request['token']);
			// $player_details = ProviderHelper::playerDetailsCall($data['token']);
			$requesttosend = [
			  "access_token" => $client_details->client_access_token,
			  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
			  "type" => "fundtransferrequest",
			  "datesent" => Helper::datesent(),
			  "gamedetails" => [
				"gameid" =>  "",
				"gamename" => ""
			  ],
			  "fundtransferrequest" => [
					"playerinfo" => [
					"client_player_id" => $client_details->client_player_id,
					"token" => $request['token'],
				],
				"fundinfo" => [
					  "gamesessionid" => "",
					  "transferid" => "",
					  "transactiontype" => 'debit',
					  "rollback" => "false",
					  "currencycode" => $client_details->default_currency,
					  "amount" => $request['data']['amount']
				]
			  ]
			];
			
			try {
				$client = new Client([
					'headers' => [ 
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer '.$client_details->client_access_token
					]
				]);
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' =>  $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
				  );

				$game_transaction_type = 1; // 1 Bet, 2 Win
				$payout_reason = 'Bet';
				$win_or_lost = 0; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
				$method = 1; // 1 bet, 2 win
				$token_id = $client_details->token_id;
				$bet_payout = 0; // Bet always 0 payout!
				$income = $request['data']['amount'];
				$provider_trans_id = $request['callback_id'];
				$round_id = $request['data']['round_id'];

				// $gamerecord = Helper::saveGame_transaction($token_id, $game_details->game_id, $request['data']['amount'],  $request['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
				// ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $round_id, $bet_payout, $game_transaction_type, $request, $response, $requesttosend, $client_response, $response);

				return $response;
			}catch(\Exception $e){
				$msg = array(
					"status" => 'error',
					"message" => $e->getMessage(),
				);
				Helper::saveLog('TGG ERROR BET', $this->provider_db_id, json_encode($request), $e->getMessage());
				return $msg;
			}
		else:
			// NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			$player_details = ProviderHelper::playerDetailsCall($request['token']);
			$client_details = ProviderHelper::getClientDetails('token', $request['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => $player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
			  );
			//Helper::saveLog('TGG Provider'.$data->callback_id, $this->provider_db_id, json_encode($request), $response);
			return $response;
		endif;
		
	}

	public function gameWin(Request $request){
		// $header = $request->header('Authorization');
    	// Helper::saveLog('TGG Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		// $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data);
        // $json_encode = json_encode($data, true);
		// $data = json_decode($json_encode);
		

		$data = [
			'token' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
			'callback_id' => 'llngrl0xem8cf',
			'name' => 'win',
			'data' => [
				'round_id' => '92611e1e06d1fcc516358a978002caaf6a82d9ec9c42703a',
				'action_id' => 'fe18c9b6550c0afc939eb311f423b207d6b2377c74ad2638',
				'final_action' => 0,
				'amount' => 2.5,
				'currency' => 'USD',
				'details' => [
					'game' => [
						'game_id' => 981,
						'absolute_name' => 'fullstate\\html5\\ugproduction\\luckylimo',
					],
					'currency_rate' => [
						'currency' => 'USD',
						'rate' => 1,
					],
					'bet' => 1,
					'total_bet' => 2.5,
					'lines' => 2.5,
					'balance_before_pay' => 9992.5000,
					'pay_for_action_this_round' => 2.5,
					'balance_after_pay' => 9990.0000,
					'final_action' => true,
				],
			],
			'signature' => 'c6a3688ca868a191bfdef4cebec089bc'
		];
		$signature_checker = $this->getSignature($this->project_id, 2, $data, $this->api_key);
		
		if(!$signature_checker):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			//Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($data), $msg);
			return $msg;
		endif;

		$existing_bet = ProviderHelper::findGameTransaction($data['data']['round_id'], 'round_id', 1); // Find if win has bet record
		$game_ext = ProviderHelper::findGameExt($data['callback_id'], 2, 'transaction_id'); // Find if this callback in game extension
		//$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $data['data']['details']['game']['game_id']);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, 1);
		
		if($game_ext == 'false'):
			if($existing_bet == 'false'): // Bet is existing, else the bet is already updated to win //temporary == make it !=
				$client_details = ProviderHelper::getClientDetails('token', $data['token']);
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
							"token" => $data['token'],
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transferid" => "",
						      "transactiontype" => 'credit',
						      "rollback" => "false",
						      "currencycode" => $client_details->default_currency,
						      "amount" => $data['data']['amount']
						]
					  ]
				];
					try {
						$client = new Client([
		                    'headers' => [ 
		                        'Content-Type' => 'application/json',
		                        'Authorization' => 'Bearer '.$client_details->client_access_token
		                    ]
		                ]);
						$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);
						$client_response = json_decode($guzzle_response->getBody()->getContents());
						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => $client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
					 	 );

						$amount = $data['data']['amount'];
				 	    $round_id = $data['data']['round_id'];
				 	    if($existing_bet->bet_amount > $amount):
		 	  				$win = 0; // lost
		 	  				$entry_id = 1; //lost
		 	  				$income = $existing_bet->bet_amount - $amount;
		 	  			else:
		 	  				$win = 1; //win
		 	  				$entry_id = 2; //win
		 	  				$income = $existing_bet->bet_amount - $amount;
						   endif;
						   
						// $this->updateBetTransaction($round_id, $amount, $income, $win, $entry_id);
						// $this->create8PTransactionExt($existing_bet->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 2, $data['data']['amount'], $data['callback_id'] ,$round_id);

					  	return $response;

					}catch(\Exception $e){
						$msg = array(
							"status" => 'error',
							"message" => $e->getMessage(),
						);
						//Helper::saveLog('TGG ERROR WIN', $this->provider_db_id, json_encode($data), $e->getMessage());
						return $msg;
					}
			else: 
					return 'This is bonuss spin no ready to function';
				    // // No Bet was found check if this is a free spin and proccess it!
				    // if($string_to_obj->game->action == 'freespin'):
				  	//     $client_details = ProviderHelper::getClientDetails('token', $data['token']);
					// 	$requesttosend = [
					// 		  "access_token" => $client_details->client_access_token,
					// 		  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					// 		  "type" => "fundtransferrequest",
					// 		  "datesent" => Helper::datesent(),
					// 		  "gamedetails" => [
					// 		     "gameid" => $game_details->game_code, // $game_details->game_code
				  	// 			 "gamename" => $game_details->game_name
					// 		  ],
					// 		  "fundtransferrequest" => [
					// 				"playerinfo" => [
					// 				"client_player_id" => $client_details->client_player_id,
					// 				"token" => $data['token'],
					// 			],
					// 			"fundinfo" => [
					// 			      "gamesessionid" => "",
					// 			      "transferid" => "", 
					// 			      "transactiontype" => 'credit',
					// 			      "rollback" => "false",
					// 			      "currencycode" => $client_details->default_currency,
					// 			      "amount" => $data['data']['amount']
					// 			]
					// 		  ]
					// 	];
					// 		try {
					// 			$client = new Client([
				    //                 'headers' => [ 
				    //                     'Content-Type' => 'application/json',
				    //                     'Authorization' => 'Bearer '.$client_details->client_access_token
				    //                 ]
				    //             ]);
					// 			$guzzle_response = $client->post($client_details->fund_transfer_url,
					// 				['body' => json_encode($requesttosend)]
					// 			);
					// 			$client_response = json_decode($guzzle_response->getBody()->getContents());

					// 			$response = array(
					// 				'status' => 'ok',
					// 				'data' => [
					// 					'balance' => (string)$client_response->fundtransferresponse->balance,
					// 					'currency' => $client_details->default_currency,
					// 				],
					// 		 	 );
					// 			$payout_reason = 'Free Spin';
					// 	 		$win_or_lost = 1; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
					// 	 		$method = 2; // 1 bet, 2 win
					// 	 	    $token_id = $client_details->token_id;
					// 	 	    $bet_payout = 0; // Bet always 0 payout!
					// 	 	    $income = '-'.$data['data']['amount']; // NEgative
					// 	 	    $provider_trans_id = $data['callback_id'];
					// 	 	    $round_id = $data['data']['round_id'];
					// 			// $game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, 0, $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
			   		// 			// $trans_ext = $this->create8PTransactionExt($game_trans, $data, $requesttosend, $client_response, $client_response,$data, 5, $data['data']['amount'], $provider_trans_id,$round_id);
					// 		  	return $response;
					// 		}catch(\Exception $e){
					// 			$msg = array(
					// 				"status" => 'error',
					// 				"message" => $e->getMessage(),
					// 			);
					// 			//Helper::saveLog('TGG ERROR FREE SPIN', $this->provider_db_id, json_encode($data), $e->getMessage());
					// 			return $msg;
					// 		}
				    // else:
				    //         //NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
					// 		$player_details = ProviderHelper::playerDetailsCall($data['token']);
					// 		$client_details = ProviderHelper::getClientDetails('token', $data['token']);
					// 		$response = array(
					// 			'status' => 'ok',
					// 			'data' => [
					// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
					// 				'currency' => $client_details->default_currency,
					// 			],
					// 	 	 );
					// 		//Helper::saveLog('TGG Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
					// 		return $response;
				    // endif;
			endif;
		else:
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => $player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
				Helper::saveLog('TGG Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
		endif;

	}

	public function gameRefund(Request $request){
		$header = $request->header('Authorization');
	    Helper::saveLog('TGG Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

	}

	public function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}
}
