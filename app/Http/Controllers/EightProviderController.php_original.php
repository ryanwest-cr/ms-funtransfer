<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;
use App\Helpers\ProviderHelper;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * 8Provider (API Version 2 POST DATA METHODS)
 *
 * @version 1.0
 * @method index
 * @method gameBet
 * @method gameWin
 * @method gameRefund
 * Available Currencies
 * AUD,BRL,BTC,CAD,CNY,COP,CZK,EUR,GBP,GHS,HKD,HRK,IDR,INR,IRR,JPY,KRW,KZT,MDL,MMK,MYR,NOK,PLN,RUB,SEK,THB,TRY,TWD,UAH,USD,VND,XOF,ZAR
 */
class EightProviderController extends Controller
{

	public $api_url = 'http://api.8provider.com';
	public $secret_key = 'c270d53d4d83d69358056dbca870c0ce';
	public $project_id = '1042';
	public $provider_db_id = 19;


    /**
     * @return string
     *
     */
	public function getSignature($system_id, $callback_version, array $args, $system_key){
	    $md5 = array();
	    $md5[] = $system_id;
	    $md5[] = $callback_version;

	    $signature = $args['signature']; // store the signature
	    unset($args['signature']); // remove signature from the array

	    $args = array_filter($args, function($val){ return !($val === null || (is_array($val) && !$val));});
	    foreach ($args as $required_arg) {
	        $arg = $required_arg;
	        if (is_array($arg)) {
	            $md5[] = implode(':', array_filter($arg, function($val){ return !($val === null || (is_array($val) && !$val));}));
	        } else {
	            $md5[] = $arg;
	        }
	    };

	    $md5[] = $system_key;
	    $md5_str = implode('*', $md5);
	    $md5 = md5($md5_str);

	    if($md5 == $signature){  // Generate Hash And Check it also!
	    	return 'true';
	    }else{
	    	return 'false';
	    }
	}

	/**
	 * @author's note single method that will handle 4 API Calls
	 * @param name = bet, win, refund,
	 * 
	 */
	public function index(Request $request){

		Helper::saveLog('8P index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->secret_key);
		if($signature_checker == 'false'):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			Helper::saveLog('8P Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
			return $msg;
		endif;

		if($request->name == 'init'){

			$game_init = $this->gameInitialize($request->all());
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
	 * @param data [array]
	 * 
	 */
	public function gameInitialize($data){
		$player_details = ProviderHelper::playerDetailsCall($data['token']);
		$client_details = ProviderHelper::getClientDetails('token', $data['token']);
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => (string)$player_details->playerdetailsresponse->balance,
				'currency' => $client_details->default_currency,
			],
	 	 );
		Helper::saveLog('8P GAME INIT', $this->provider_db_id, json_encode($data), $response);
	  	return $response;
	}

	/**
	 * [gameBet]
	 * @author's note [FLOW] [Look for bet transaction if dont process the callback]
	 * @param  [array] $data [array data from the index method]
	 * 
	 */
	public function gameBet($data){
			$game_ext = $this->findGameExt($data['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension
		    if($game_ext == 'false'): // NO BET
				// DECODE THE JSON_STRING
			    $array = (array)$data['data']['details'];
			    $newStr = str_replace("\\", '', $array[0]);
			    $newStr2 = str_replace(';', '', $newStr);
			    $string_to_obj = json_decode($newStr2);
			    $game_id = $string_to_obj->game->game_id;
			    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);	

			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
			   	if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
			   		$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
					);
					return $msg;
			   	endif;

			    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
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
						"token" => $data['token'],
					],
					"fundinfo" => [
					      "gamesessionid" => "",
					      "transferid" => "",
					      "transactiontype" => 'debit',
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
							'balance' => (string)$client_response->fundtransferresponse->balance,
							'currency' => $client_details->default_currency,
						],
				 	 );
			 		$payout_reason = 'Bet';
			 		$win_or_lost = 0; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
			 		$method = 1; // 1 bet, 2 win
			 	    $token_id = $client_details->token_id;
			 	    $bet_payout = 0; // Bet always 0 payout!
			 	    $income = $data['data']['amount'];
			 	    $provider_trans_id = $data['callback_id'];
			 	    $round_id = $data['data']['round_id'];
					$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $data['data']['amount'],  $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
			   		$trans_ext = $this->create8PTransactionExt($game_trans, $data, $requesttosend, $client_response, $client_response,$data, 1, $data['data']['amount'], $provider_trans_id,$round_id);
				  	return $response;
				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					Helper::saveLog('8P ERROR BET', $this->provider_db_id, json_encode($data), $e->getMessage());
					return $msg;
				}
		    else:
		    	// NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
		    	$player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				Helper::saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
		    endif;
	}

	/**
	 * [gameWin description]
	 * @author's note [FLOW] [Look for bet transaction if found update the transaction and if not return error bet not found ]
	 * @param  [array] $data [array data from the index method]
	 *
	 */
	public function gameWin($data){

		$array = (array)$data['data']['details'];
	    $newStr = str_replace("\\", '', $array[0]);
	    $newStr2 = str_replace(';', '', $newStr);
	    $string_to_obj = json_decode($newStr2);
	    $game_id = $string_to_obj->game->game_id;
	    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
	    // return $game_details;

		$existing_bet = $this->findGameTransaction($data['data']['round_id'], 'round_id', 1); // Find if win has bet record
		$game_ext = $this->findGameExt($data['callback_id'], 2, 'transaction_id'); // Find if this callback in game extension
		if($game_ext == 'false'):
			if($existing_bet != 'false'): // Bet is existing, else the bet is already updated to win
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
								'balance' => (string)$client_response->fundtransferresponse->balance,
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
						$this->updateBetTransaction($round_id, $amount, $income, $win, $entry_id);
						$this->create8PTransactionExt($existing_bet->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 2, $data['data']['amount'], $data['callback_id'] ,$round_id);

					  	return $response;

					}catch(\Exception $e){
						$msg = array(
							"status" => 'error',
							"message" => $e->getMessage(),
						);
						Helper::saveLog('8P ERROR WIN', $this->provider_db_id, json_encode($data), $e->getMessage());
						return $msg;
					}
			else: 
				    // No Bet was found check if this is a free spin and proccess it!
				    if($string_to_obj->game->action == 'freespin'):
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
										'balance' => (string)$client_response->fundtransferresponse->balance,
										'currency' => $client_details->default_currency,
									],
							 	 );
								$payout_reason = 'Free Spin';
						 		$win_or_lost = 1; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
						 		$method = 2; // 1 bet, 2 win
						 	    $token_id = $client_details->token_id;
						 	    $bet_payout = 0; // Bet always 0 payout!
						 	    $income = '-'.$data['data']['amount']; // NEgative
						 	    $provider_trans_id = $data['callback_id'];
						 	    $round_id = $data['data']['round_id'];
								$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, 0, $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
			   					$trans_ext = $this->create8PTransactionExt($game_trans, $data, $requesttosend, $client_response, $client_response,$data, 5, $data['data']['amount'], $provider_trans_id,$round_id);
							  	return $response;
							}catch(\Exception $e){
								$msg = array(
									"status" => 'error',
									"message" => $e->getMessage(),
								);
								Helper::saveLog('8P ERROR FREE SPIN', $this->provider_db_id, json_encode($data), $e->getMessage());
								return $msg;
							}
				    else:
				            //NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
							$player_details = ProviderHelper::playerDetailsCall($data['token']);
							$client_details = ProviderHelper::getClientDetails('token', $data['token']);
							$response = array(
								'status' => 'ok',
								'data' => [
									'balance' => (string)$player_details->playerdetailsresponse->balance,
									'currency' => $client_details->default_currency,
								],
						 	 );
							Helper::saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
							return $response;
				    endif;
			endif;
		else:
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
				Helper::saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
		endif;
	}

	/**
	 * [GAME REFUND]
	 * @author's note [if bet was found send it as credit, and if win was found send it as debit]
	 * @param  [array] $data [array data from the index method]
	 * 
	 */
	public function gameRefund($data){
		$array = (array)$data['data']['details'];
	    $newStr = str_replace("\\", '', $array[0]);
	    $newStr2 = str_replace(';', '', $newStr);
	    $string_to_obj = json_decode($newStr2);
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


	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	public  function create8PTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
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

	/**
	 * Find Game Transaction
	 * @param [string] $[identifier] [<ID of the game transaction>]
	 * @param [int] $[type] [<transaction_id, round_id, refundbet>]
	 * @param [int] $[entry_type] [<1 bet/debit, 2 win/credit>]
	 * 
	 */
    public  function findGameTransaction($identifier, $type, $entry_type='') {
		$transaction_db = DB::table('game_transactions as gt')
				    	->select('gt.*', 'gte.transaction_detail')
					    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
	 				   
	    if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gt.provider_trans_id", "=", $identifier],
		 		["gt.entry_id", "=", $entry_type],
		 	]);
		}
		if ($type == 'game_transaction') {
			$transaction_db->where([
		 		["gt.game_trans_id", "=", $identifier],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gt.round_id", "=", $identifier],
		 		["gt.entry_id", "=", $entry_type],
		 	]);
		}
		if ($type == 'refundbet') { // TEST
			$transaction_db->where([
		 		["gt.round_id", "=", $identifier],
		 		["gt.entry_id", "=", $entry_type],
		 	]);
		}
		$result= $transaction_db
 			->first();
		return $result ? $result : 'false';
	}

	/**
	 * Find Game Transaction Ext
	 * @param [string] $[provider_transaction_id] [<provider transaction id>]
	 * @param [int] $[game_transaction_type] [<1 bet, 2 win, 3 refund>]
	 * @param [string] $[type] [<transaction_id, round_id>]
	 * 
	 */
	public  function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
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
