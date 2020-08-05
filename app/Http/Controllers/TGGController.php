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
	 public $provider_db_id = 29; // this is not final provider no register local

	 public function callBack(Request $request){
		
		Helper::saveLog('TGG index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key,'check_signature');
		
		if($signature_checker == 'false'):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
			return $msg;
		endif;

		if($request->name == 'init'){

			$game_init = $this->gameInit($request->all());
			return json_encode($game_init);

		}elseif($request->name == 'bet'){

			$bet_handler = $this->gameBet($request->all());
			return json_encode($bet_handler);

		}elseif($request->name == 'win'){

			$win_handler = $this->gameWin($request->all());
			return json_encode($win_handler);

		}elseif($request->name == 'refund'){

			$refund_handler = $this->gameRefund($request->all());
			return json_encode($refund_handler);
		}
	}

	/**
	* $system_id - your project ID (number)
	* $version - API version (number)for API request OR callback version (number) for callback call
	* $args - array with API method OR callback parameters. API method parameters list you can find in the API method description
	* $system_key - your API key (secret key)
	*/ 
	
	public static function getSignature($system_id, $version, array $args, $system_key,$type){
		$md5 = array();
		$md5[] = $system_id;
		$md5[] = $version;
		
	
		if($type == 'check_signature'){
			$signature = $args['signature']; // store the signature
			unset($args['signature']); // remove signature from the array
		}

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
		if($type == 'check_signature'){
			if($md5 == $signature){  // Generate Hash And Check it also!
				return 'true';
			}else{
				return 'false';
			}
		}elseif($type == 'get_signature') {
			return $md5;
		}
	}

	public function getGamelist(Request $request){
		$data = [
			'signature' => 'e5e1757feaf0301856ad9c309741f283',
		];
		$signature =  $this->getSignature($this->project_id, 1,$data,$this->api_key,'get_signature');
		
		$url = $this->api_url.'/game/getlist';
        $requesttosend = [
            'project' =>  $this->project_id,
			'version' => 1 ,
			'signature' => $signature
		
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
		$token = 'n58ec5e159f769ae0b7b3a0774fdbf80';
		$client_player_details = ProviderHelper::getClientDetails('token', $token);
        $requesttosend = [
          "project" => config('providerlinks.tgg.project_id'),
          "version" => 1,
          "token" => $token,
          "game" => 498, //game_code, game_id
          "settings" =>  [
            'user_id'=> $client_player_details->player_id,
            'language'=> $client_player_details->language ? $client_player_details->language : 'en',
          ],
          "denomination" => '1', // game to be launched with values like 1.0, 1, default
          "currency" => $client_player_details->default_currency,
          "return_url_info" => 1, // url link
          "callback_version" => 2, // POST CALLBACK
        ];
        
        $signature =  ProviderHelper::getSignature($requesttosend, $this->api_key);
        $requesttosend['signature'] = $signature;
		$url = $this->api_url.'/game/getURL';
		$client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($url,[
            'form_params' => $requesttosend,
        ]);
        $res = json_decode($response->getBody(),TRUE);
        return isset($res['data']['link']) ? $res['data']['link'] : false;
	}
	/**
	 * Initialize the balance 
	 */
	public function gameInit($request){
		$data = $request;
		$token = $data['token'];
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
				Helper::saveLog('TGG Balance Response '.$data['name'], $this->provider_db_id, json_encode($data), $data_response);
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
			Helper::saveLog('TGG ERROR '.$data['name'], $this->provider_db_id,  json_encode($data), $data_response);
			return $data_response;
		}
	}

	public function gameBet($request){
		// $data = [
		// 	'token' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
		// 	'callback_id' => 'llngrl0xem8cf',
		// 	'name' => 'bet',
		// 	'data' => [
		// 		'round_id' => '92611e1e06d1fcc516358a978002caaf6a82d9ec9c42703a',
		// 		'action_id' => 'fe18c9b6550c0afc939eb311f423b207d6b2377c74ad2638',
		// 		'amount' => 2.5,
		// 		'currency' => 'USD',
		// 		'details' => [
		// 			'game' => [
		// 				'game_id' => 981,
		// 				'absolute_name' => 'fullstate\\html5\\ugproduction\\luckylimo',
		// 			],
		// 			'currency_rate' => [
		// 				'currency' => 'USD',
		// 				'rate' => 1,
		// 			],
		// 			'bet' => 1,
		// 			'total_bet' => 2.5,
		// 			'lines' => 2.5,
		// 			'balance_before_pay' => 9992.5000,
		// 			'pay_for_action_this_round' => 2.5,
		// 		],
		// 	],
		// 	'signature' => '0c3cc9263b36a54ba868f47b7a1627e3'
		// ];
		// return $data;
		Helper::saveLog('TGG '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key,'check_signature');
		if($signature_checker == 'false'):
			$msg = array(
						"status" => 'error',
						"error" => [
							"scope" => "user",
							"no_refund" => 1,
							"message" => "Signature is invalid!"
						]
					);
			Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
			return $msg;
		endif;
		$game_ext = $this->findGameExt($request['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension
		
		if($game_ext != 'false'): // NO BET
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $request["data"]["details"]["game"]["game_id"]);	
			$player_details = ProviderHelper::playerDetailsCall($request['token']);
			//if the amount is grater than to the bet amount  error message
			if($player_details->playerdetailsresponse->balance < $request['data']['amount']):
				$msg = array(
					"status" => 'error',
					"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
				);
				Helper::saveLog('TGG not enough balance '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
				return $msg;
			endif;
			$client_details = ProviderHelper::getClientDetails('token', $request['token']);
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

				$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $request['data']['amount'],  $request['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
				$trans_ext = $this->creteTGGtransaction($game_trans,  json_encode($request->all()), $requesttosend, $client_response, $client_response, json_encode($request->all()), 1, $request['data']['amount'], $provider_trans_id,$round_id);
			   return $response;
			}catch(\Exception $e){
				$msg = array(
					"status" => 'error',
					"message" => $e->getMessage(),
				);
				Helper::saveLog('TGG ERROR BET'.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
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
			Helper::saveLog('TGG Provider '.$request->name.' '.$request['callback_id'], $this->provider_db_id, json_encode($request->all()), $response);
			return $response;
		endif;
		
	}

	public function gameWin($request){
		// $header = $request->header('Authorization');
    	// Helper::saveLog('TGG Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		// $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data);
        // $json_encode = json_encode($data, true);
		// $data = json_decode($json_encode);

		// $data = [
		// 	'token' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
		// 	'callback_id' => 'llngrl0xem8cf',
		// 	'name' => 'win',
		// 	'data' => [
		// 		'round_id' => '92611e1e06d1fcc516358a978002caaf6a82d9ec9c42703a',
		// 		'action_id' => 'fe18c9b6550c0afc939eb311f423b207d6b2377c74ad2638',
		// 		'final_action' => 0,
		// 		'amount' => 2.5,
		// 		'currency' => 'USD',
		// 		'details' => [
		// 			'game' => [
		// 				'game_id' => 981,
		// 				'absolute_name' => 'fullstate\\html5\\ugproduction\\luckylimo',
		// 			],
		// 			'currency_rate' => [
		// 				'currency' => 'USD',
		// 				'rate' => 1,
		// 			],
		// 			'bet' => 1,
		// 			'total_bet' => 2.5,
		// 			'lines' => 2.5,
		// 			'balance_before_pay' => 9992.5000,
		// 			'pay_for_action_this_round' => 2.5,
		// 			'balance_after_pay' => 9990.0000,
		// 			'final_action' => true,
		// 		],
		// 	],
		// 	'signature' => 'c6a3688ca868a191bfdef4cebec089bc'
		// ];
		$data = $request->all();
		$signature_checker = $this->getSignature($this->project_id, 2, $data, $this->api_key,'signature');
		
		if($signature_checker == 'false'):
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
				 Helper::saveLog('TGG Provider '.$request->name.' '.$request['callback_id'], $this->provider_db_id, json_encode($request->all()), $response);
				return $response;
		endif;

	}

	public function gameRefund($request){
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

	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	public function creteTGGtransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" => json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}

}
