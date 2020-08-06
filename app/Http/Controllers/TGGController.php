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

	public function index(Request $request){
		
		Helper::saveLog('TGG index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key,'_signature');
		// return $signature_checker;
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
		$signature_checker = $this->getSignature($this->project_id, 2, $request, $this->api_key,'check_signature');
		if($signature_checker == 'false'):
			$msg = array(
						"status" => 'error',
						"error" => [
							"scope" => "user",
							"no_refund" => 1,
							"message" => "Signature is invalid!"
						]
					);
			Helper::saveLog('TGG Signature Failed '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
			return $msg;
		endif;
		
		$game_ext = $this->findGameExt($request['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension

		if($game_ext == 'false'): // NO BET
		
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $request["data"]["details"]["game"]["game_id"]);
			$player_details = ProviderHelper::playerDetailsCall($request['token']);
			
			//if the amount is grater than to the bet amount  error message
			if($player_details->playerdetailsresponse->balance < $request['data']['amount']):
				$msg = array(
					"status" => 'error',
					"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
				);
				Helper::saveLog('TGG not enough balance '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
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
				$token_id = $client_details->token_id;
				$game_id = $game_details->game_id;
				$bet_amount =  $request['data']['amount'];
				$payout = 0;
				$entry_id = 1; //1 bet , 2win
				$win = 0;// 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
				$payout_reason = 'Bet';
				$income = 0;
				$provider_trans_id = $request['callback_id'];
				$round_id = $request['data']['round_id'];

				$gametransaction_id = Helper::saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win, null, $payout_reason , $income, $provider_trans_id, $round_id);
				
				$provider_request = json_encode($request);
				$mw_request = $requesttosend;
				$mw_response = $client_response;
				$client_response = $client_response;
				$transaction_detail = $client_response;
				$game_transaction_type = 1;

				$this->creteTGGtransaction($gametransaction_id, $provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $bet_amount, $provider_trans_id, $round_id);
				
				Helper::saveLog('TGG PROCESS '.$request['name'], $this->provider_db_id, json_encode($request), $response);
			   return $response;
			}catch(\Exception $e){
				$msg = array(
					"status" => 'error',
					"message" => $e->getMessage(),
				);
				Helper::saveLog('TGG ERROR BET'.$request['name'], $this->provider_db_id, json_encode($request), $msg);
				return $msg;
			}
		else:
			// NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			if($game_ext->provider_trans_id == $request["callback_id"]): //if same duplicate
				$player_details = ProviderHelper::playerDetailsCall($request['token']);
				$client_details = ProviderHelper::getClientDetails('token', $request['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => $player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
	
				Helper::saveLog('TGG second bet '.$request['name'].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
				return $response;
			else:
				$msg = array(
					"status" => 'error',
					"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
				);
				Helper::saveLog('TGG error second '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
				return $msg;
			endif;
		endif;
	}

	public function gameWin($request){
		$existing_bet = ProviderHelper::findGameTransaction($request['data']['round_id'], 'round_id', 1); // Find if win has bet record
		$game_ext = ProviderHelper::findGameExt($request['callback_id'], 2, 'transaction_id'); // Find if this callback in game extension
	
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $request['data']['details']['game']['game_id']);
		// $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, 1);
		
		if($game_ext == 'false'):
			if($existing_bet != 'false'): // Bet is existing, else the bet is already updated to win //temporary == make it !=
				
				$client_details = ProviderHelper::getClientDetails('token', $request['token']);
				
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
							"token" => $request['token'],
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transferid" => "",
						      "transactiontype" => 'credit',
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
								'balance' => $client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
					 	 );

						$amount = $request['data']['amount'];
				 	    $round_id = $request['data']['round_id'];
				 	    if($existing_bet->bet_amount > $amount):
		 	  				$win = 0; // lost
		 	  				$entry_id = 1; //lost
		 	  				$income = $existing_bet->bet_amount - $amount;
		 	  			else:
		 	  				$win = 1; //win
		 	  				$entry_id = 2; //win
		 	  				$income = $existing_bet->bet_amount - $amount;
						   endif;
						   
						$this->updateBetTransaction($round_id, $amount, $income, $win, $entry_id);
						$this->creteTGGtransaction($existing_bet->game_trans_id, $request, $requesttosend, $client_response, $client_response,$request, 2, $request['data']['amount'], $request['callback_id'] ,$round_id);
						
						Helper::saveLog('TGG success '.$request['data']['round_id'], $this->provider_db_id, json_encode($request), $response);   
					  	return $response;

					}catch(\Exception $e){
						$msg = array(
							"status" => 'error',
							"message" => $e->getMessage(),
						);

						Helper::saveLog('TGG ERROR '.$request["name"], $this->provider_db_id, json_encode($request), $e->getMessage());
						return $msg;
					}
			// else: 
			// 		return 'This is bonuss spin no ready to function';
				    // // No Bet was found check if this is a free spin and proccess it!
				    // if($string_to_obj->game->action == 'freespin'):
				  	//     $client_details = ProviderHelper::getClientDetails('token', $request['token']);
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
					// 				"token" => $request['token'],
					// 			],
					// 			"fundinfo" => [
					// 			      "gamesessionid" => "",
					// 			      "transferid" => "", 
					// 			      "transactiontype" => 'credit',
					// 			      "rollback" => "false",
					// 			      "currencycode" => $client_details->default_currency,
					// 			      "amount" => $request['data']['amount']
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
					// 	 	    $income = '-'.$request['data']['amount']; // NEgative
					// 	 	    $provider_trans_id = $request['callback_id'];
					// 	 	    $round_id = $request['data']['round_id'];
					// 			// $game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, 0, $request['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
			   		// 			// $trans_ext = $this->create8PTransactionExt($game_trans, $request, $requesttosend, $client_response, $client_response,$request, 5, $request['data']['amount'], $provider_trans_id,$round_id);
					// 		  	return $response;
					// 		}catch(\Exception $e){
					// 			$msg = array(
					// 				"status" => 'error',
					// 				"message" => $e->getMessage(),
					// 			);
					// 			//Helper::saveLog('TGG ERROR FREE SPIN', $this->provider_db_id, json_encode($request), $e->getMessage());
					// 			return $msg;
					// 		}
				    // else:
				    //         //NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
					// 		$player_details = ProviderHelper::playerDetailsCall($request['token']);
					// 		$client_details = ProviderHelper::getClientDetails('token', $request['token']);
					// 		$response = array(
					// 			'status' => 'ok',
					// 			'data' => [
					// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
					// 				'currency' => $client_details->default_currency,
					// 			],
					// 	 	 );
					// 		//Helper::saveLog('TGG Provider'.$request['data']['round_id'], $this->provider_db_id, json_encode($request), $response);
					// 		return $response;
				    // endif;
			endif;
		else:
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
				if($game_ext->provider_trans_id == $request["callback_id"]): //if same duplicate
					$player_details = ProviderHelper::playerDetailsCall($request['token']);
					$client_details = ProviderHelper::getClientDetails('token', $request['token']);
					$response = array(
						'status' => 'ok',
						'data' => [
							'balance' => $player_details->playerdetailsresponse->balance,
							'currency' => $client_details->default_currency,
						],
					 );
					 Helper::saveLog('TGG Provider '.$request["name"].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
					return $response;
				else:
					$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
					);
					Helper::saveLog('TGG error second '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
					return $msg;
				endif;

				
		endif;

	}

	public function gameRefund($data){

		$player_details = ProviderHelper::playerDetailsCall($data['token']);
		$client_details = ProviderHelper::getClientDetails('token', $data['token']);
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => (string)$player_details->playerdetailsresponse->balance,
				'currency' => $client_details->default_currency,
			],
		 );
		Helper::saveLog('TGG Refund '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
		return $response;


		$array = (array)$data['data']['details'];
	    $newStr = str_replace("\\", '', $array[0]);
	    $newStr2 = str_replace(';', '', $newStr);
		$string_to_obj = json_decode($newStr2);
		dd($string_to_obj);
	    $game_id = $string_to_obj->game->game_id;
	    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
		$game_refund = $this->findGameExt($data['callback_id'], 4, 'transaction_id'); // Find if this callback in game extension	
		if($game_refund == 'false'): // NO REFUND WAS FOUND PROCESS IT!
		
		$game_transaction_ext = $this->findGameExt($data['data']['refund_round_id'], 1, 'round_id'); // Find GameEXT
		if($game_transaction_ext == 'false'):
		    $player_details = ProviderHelper::playerDetailsCall($data['token']);
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
		 	);
			Helper::saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
			return $response;
		endif;

		$game_transaction_ext_refund = $this->findGameExt($data['data']['refund_round_id'], 4, 'round_id'); // Find GameEXT
		if($game_transaction_ext_refund != 'false'):
		    $player_details = ProviderHelper::playerDetailsCall($data['token']);
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
		 	);
			Helper::saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
			return $response;
		endif;


		$existing_transaction = $this->findGameTransaction($game_transaction_ext->game_trans_id, 'game_transaction');
		if($existing_transaction != 'false'): // IF BET WAS FOUND PROCESS IT!
			$transaction_type = $game_transaction_ext->game_transaction_type == 1 ? 'credit' : 'debit'; // 1 Bet
		    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
		    if($transaction_type == 'debit'):
			   	$player_details = ProviderHelper::playerDetailsCall($data['token']);
			   	if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
			   		$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
					);
					return $msg;
			   	endif;
		    endif;
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
				      "transactiontype" => $transaction_type,
				      "rollback" => "true",
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
						'balance' => (string)$client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				$this->updateBetTransaction($existing_transaction->round_id, $existing_transaction->pay_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
				$this->create8PTransactionExt($existing_transaction->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 4, $data['data']['amount'], $data['callback_id'], $data['data']['refund_round_id']);
			  	return $response;

			}catch(\Exception $e){
				$msg = array(
					"status" => 'error',
					"message" => $e->getMessage(),
				);
				Helper::saveLog('8P ERROR REFUND', $this->provider_db_id, json_encode($data), $e->getMessage());
				return $msg;
			}
		else:
			// NO BET WAS FOUND DO NOTHING
			$player_details = ProviderHelper::playerDetailsCall($data['token']);
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
		 	 );
			Helper::saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
			return $response;
		endif;
		else:
			// NOTE IF CALLBACK WAS ALREADY PROCESS/DUPLICATE PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			$player_details = ProviderHelper::playerDetailsCall($data['token']);
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
		 	 );
			Helper::saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
			return $response;
		endif;
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


	/**
	 * Find bet and update to win 
	 * @param [int] $[round_id] [<ID of the game transaction>]
	 * @param [int] $[pay_amount] [<amount to change>]
	 * @param [int] $[income] [<bet - payout>]
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public  function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
		$update = DB::table('game_transactions')
			 ->where('round_id', $round_id)
			 ->update(['pay_amount' => $pay_amount, 
				   'income' => $income, 
				   'win' => $win, 
				   'entry_id' => $entry_id,
				   'transaction_reason' => $this->updateReason($win),
			 ]);
	 return ($update ? true : false);
 	}

	/**
	 * Find bet and update to win 
	* @param [int] $[win] [< Win TYPE>][<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	* 
	*/
	public  function updateReason($win) {
		$win_type = [
		"1" => 'Transaction updated to win',
		"2" => 'Transaction updated to bet',
		"3" => 'Transaction updated to Draw',
		"4" => 'Transaction updated to Refund',
		"5" => 'Transaction updated to Processing',
		];
		if(array_key_exists($win, $win_type)){
			return $win_type[$win];
		}else{
			return 'Transaction Was Updated!';
		}
	}

}
