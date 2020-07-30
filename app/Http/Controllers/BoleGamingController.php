<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\GameTransaction;
use App\Helpers\GameSubscription;
use App\Helpers\GameRound;
use App\Helpers\Game;
use App\Helpers\CallParameters;

use DB;
use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

// BOle Gaming TEST PP
class BoleGamingController extends Controller
{

   		public $AccessKeyId, $access_key_secret, $app_key, $login_url, $logout_url;
   		public $provider_db_id = 11;


		public function changeConfig($type, $identifier){
			$client_details = ProviderHelper::getClientDetails($type, $identifier);
			if($client_details != null){
				$this->AccessKeyId = config('providerlinks.bolegaming.'.$client_details->default_currency.'.AccessKeyId');
			    $this->access_key_secret = config('providerlinks.bolegaming.'.$client_details->default_currency.'.access_key_secret');
	   		    $this->app_key = config('providerlinks.bolegaming.'.$client_details->default_currency.'.app_key');
	   		    $this->login_url = config('providerlinks.bolegaming.'.$client_details->default_currency.'.login_url');
	   		    $this->logout_url = config('providerlinks.bolegaming.'.$client_details->default_currency.'.logout_url');
			}else{
				return false;
			}
		}

		/**
		 * generated signature
		 */	
		public function generateSign()
		{
			$nonce = rand();
			$timestamp = time();
			$key = $this->access_key_secret.$nonce.$timestamp;
			$signature = sha1($key);

			$sign = [
				"timestamp" => $timestamp,
				"nonce" => $nonce,
				"signature" => $signature,
			];

      	    return $sign;
		}

		/**
		 * Verify Http Request // UPDATE v2
		 * @param Operator ID, Player Account, sha1 Encryption, Operator sub id
		 */	
		public function chashen($operator_id, $player_account, $cha, $operator_sub_id=""){

			$app_key = $this->app_key;
		    $chashen = 'operator_id='.$operator_id.'&operator_sub_id='.$operator_sub_id.'&player_account='.$player_account.'&app_key='.$app_key.'';
			// $signature = hex2bin($cha_ashen);
			$cha1 = hash_hmac('sha1', $chashen, $app_key, true);
			$cha4 = base64_encode($cha1);
			$cha3 = strtoupper($cha4);

			if($cha == $cha3){
				return true;
			}else{
				return false;
			}
		}

		/**
		 *  NOT USED!
		 *  Logout the player
		 */	
		public function playerLogout(Request $request)
		{

			 Helper::saveLog('BOLE_LOGOUT', $this->provider_db_id, 'logouted', 'BOLE CALL');
			 $sign = $this->generateSign();

			 $http = new Client();
	         // $response = $http->post('https://api.cdmolo.com:16800/v1/player/logout', [
	         $response = $http->post($this->logout_url, [
	            'form_params' => [
	                'player_account' => $request->username,
	                'AccessKeyId'=> $this->AccessKeyId,
	                'Timestamp'=> $sign['timestamp'],
	                'Nonce'=> $sign['nonce'],
	                'Sign'=> $sign['signature']
	            ],
	         ]);

	         $client_response = $response->getBody()->getContents();
	         return $client_response;
		}


		/**
		 *  NOT USED!
		 *  TEST
		 *  Get 30 Day Game Records
		 */	
		public function get30DayGameRecord()
		{
			 $sign = $this->generateSign();

			 $http = new Client();
	         // $response = $http->post('https://api.cdmolo.com:16800/v1/player/login', [
	         // $response = $http->post(config('providerlinks.bolegaming.logout_url'), [
	         $response = $http->post($this->logout_url, [
	            'form_params' => [
	                'start_time' => time(),
	                'end_time' => $request->username,
	                'game_code'=> $request->country_code,
	                'AccessKeyId'=> $this->AccessKeyId,
	                'Timestamp'=> $sign['timestamp'],
	                'Nonce'=> $sign['nonce'],
	                'Sign'=> $sign['signature']
	            ],
	         ]);

	         $client_response = $response->getBody()->getContents();
	         return $client_response;
		}


		/**
		 *  Balance Update 
		 *      3 Types Of Game
		 *  	Slot Games
		 *  	Table Games (Mahjong)
		 *  	Table Games (BlackJack and Poker)
		 */	
		public function playerWalletCost(Request $request)
		{

			$json_data = json_decode($request->getContent());
			// Helper::saveLog('BOLE WALLET CALL', $this->provider_db_id, $request->getContent(), 'boleReq');
			
		    // dd($game_details);
			// Helper::saveLog('WALLET CALL BOLE', $this->provider_db_id, '$this->provider_db_id', 'BOLE CALL');
			Helper::saveLog('BOLE WALLET CALL', $this->provider_db_id, $request->getContent(), 'boleReq');
			$client_details = ProviderHelper::getClientDetails('player_id', $json_data->player_account);
			$client_currency_check = ProviderHelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
			if($client_currency_check == 'false'){
				$data = [
						"resp_msg" => [
							"code" => 43900,
							"message" => 'game service error',
							"errors" => []
						]
				];
				return $data;
			}
			$this->changeConfig('player_id', $json_data->player_account);
			$data = [
				"resp_msg" => [
					"code" => 43101,
					"message" => 'the user does not exist',
					"errors" => []
				]
			];

			$hashen = $this->chashen($json_data->operator_id, $json_data->player_account, $json_data->sha1);
			if(!$hashen){
		        $data = [
					"resp_msg" => [
						"code" => 43006,
						"message" => 'signature error',
						"errors" => []
					]
				];
		        Helper::saveLog('BOLE UNKNOWN CALL', $this->provider_db_id, $request->getContent(), 'UnknownboleReq');
				return $data;
			}
			// $client_details->default_currency
			if($client_details)
			{
						$client = new Client([
						    'headers' => [ 
						    	'Content-Type' => 'application/json',
						    	'Authorization' => 'Bearer '.$client_details->client_access_token
						    ]
						]);
				
						// IF COST_INFO HAS DATA
						if(count(get_object_vars($json_data->cost_info)) != 0){
							
								//This area are use to update game_transaction table bet_amount,win or lose, pay_amount, and entry_type
								
							    $transaction_type = $json_data->cost_info->gain_gold < 0 ? 'debit' : 'credit';

							    // TRAP SLOT GAMES FOR DB QUERY
							    // $game_details = Game::find($json_data->game_code);
							    if($json_data->game_code == 'slot'){
							    	$game_details = Game::find($json_data->game_code.'_'.$json_data->cost_info->scene);
							    }else{
							    	$game_details = Game::find($json_data->game_code);
							    }

							    if($game_details == false){
						    		$data = [
										"resp_msg" => [
											"code" => 43201,
											"message" => 'the game does not exist',
											"errors" => []
										]
									];
									return $data;
							    }
							    $db_game_name = $game_details->game_name;
	    						$db_game_code = $game_details->game_code;

								$token_id = $client_details->token_id;
				                $bet_amount = abs($json_data->cost_info->bet_num);
								
								//Updated By Sir Randy
								$pay_amount = abs($json_data->cost_info->gain_gold);

								// WIN LOST OR DRAW 
								$win_or_lost = $transaction_type == 'debit' ? 0 : 1;


								// SLot Games
								if(	$json_data->game_code == 'slot') {
									$income = $bet_amount - $json_data->amount;									
									$pay_amount = $json_data->amount;
									$transaction_type = $pay_amount == 0 ? 'debit' : 'credit';
									$win_or_lost = $pay_amount == 0 ? 0 : 1;
								}

								// Multi Games / Baccarat and rbwar
				                if($json_data->game_code == 'baccarat' || $json_data->game_code == 'rbwar'){

				                	$income = $bet_amount - $json_data->amount;	
									$pay_amount = abs($json_data->amount); // amount should be used here for logging!

									if($json_data->cost_info->gain_gold  == 0){
										$win_or_lost = 3; //For draw!
										$income = $bet_amount - $json_data->amount;	
									}elseif($json_data->cost_info->gain_gold  < 0){
										$income = $bet_amount - $json_data->amount;	
									}
				                }

								// Contest Games / Mahjongs, BlackJack
								if($json_data->game_code == 'blackjack' || 
								   $json_data->game_code == 'ermj' || 
								   $json_data->game_code == 'gyzjmj' || 
								   $json_data->game_code == 'hbmj' || 
								   $json_data->game_code == 'hzmj' || 
								   $json_data->game_code == 'hnmj' || 
								   $json_data->game_code == 'gdmj' || 
								   $json_data->game_code == 'dzmj' || 
								   $json_data->game_code == 'zjh' || 
								   $json_data->game_code == 'sangong' || 
								   $json_data->game_code == 'tbnn' || 
								   $json_data->game_code == 'qydz' || 
								   $json_data->game_code == 'blnn' || 
								   $json_data->game_code == 'mjxzdd' || 
								   $json_data->game_code == 'mjxlch'){
									
									$pay_amount = $json_data->cost_info->gain_gold;
									$income = $bet_amount - $json_data->cost_info->gain_gold;	

									if($json_data->cost_info->gain_gold  == 0){
										$income = 0; // If zero it means it was a draw	
										$win_or_lost = 3; // DRAW
									}elseif($json_data->cost_info->gain_gold  < 0){ 
									    // NEGATIVE GAIN_GOLD IT MEANS LOST! and GAIN_GOLD WILL BE ALWAYS BET_NUM negative value
										$pay_amount = 0; // IF NEGATIVE PUT IT AS ZERO
										$income = $bet_amount - $pay_amount;	
									}else{
										$pay_amount_income = $bet_amount + $json_data->cost_info->gain_gold;
										$income = $bet_amount - $pay_amount_income;	
										$pay_amount = $json_data->cost_info->gain_gold;
									}
									
				                }

								
								//?????? // ENTRY_TYPE
				                $method = $transaction_type == 'debit' ? 1 : 2;

				                // dd($win_or_lost);

				                $payout_reason = $json_data->cost_info->taxes > 0 ? 'with tax deduction' : null;
								// $gamerecord = Helper::saveGame_transaction($token_id, $game_details->game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, $income, $payout_reason);


								$provider_trans_id = $json_data->report_id;
								$gamerecord  = Helper::saveGame_transaction($token_id, $game_details->game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id);

								// dd($win_or_lost);
								// if($payout_reason != null){
									$game_transextension = Helper::saveGame_trans_ext($gamerecord, $request->getContent());
								// }



								// 1.2 CHANGE THE PLAYED_AMOUNT BACK TO ORIGINAL AMOUNT FOR THIS GAMES
								if($json_data->game_code == 'blackjack' || 
								   $json_data->game_code == 'ermj' || 
								   $json_data->game_code == 'gyzjmj' || 
								   $json_data->game_code == 'hbmj' || 
								   $json_data->game_code == 'hzmj' || 
								   $json_data->game_code == 'hnmj' || 
								   $json_data->game_code == 'gdmj' || 
								   $json_data->game_code == 'dzmj' || 
								   $json_data->game_code == 'zjh' || 
								   $json_data->game_code == 'sangong' || 
								   $json_data->game_code == 'tbnn' || 
								   $json_data->game_code == 'qydz' || 
								   $json_data->game_code == 'blnn' || 
								   $json_data->game_code == 'mjxzdd' || 
								   $json_data->game_code == 'mjxlch'){  // Table Games

										// $pay_amount = abs($json_data->amount);
										// $transaction_type = 'credit';


										if($json_data->cost_info->gain_gold  == 0){
											$pay_amount = $json_data->cost_info->gain_gold;
										}elseif($json_data->cost_info->gain_gold  < 0){ 
										    $transaction_type = 'debit';
											$pay_amount = $json_data->cost_info->gain_gold;
										}else{
											$pay_amount = $json_data->cost_info->gain_gold;
											// $income = $bet_amount - $pay_amount;	
										}


								}elseif($json_data->game_code == 'slot'){
										$pay_amount = abs($json_data->amount);
										$transaction_type = 'credit';
								}elseif($json_data->game_code == 'baccarat' || $json_data->game_code == 'rbwar'){ 
										$pay_amount = abs($json_data->amount);
										$transaction_type = 'credit';
								}


					            try
								{	

									// CALL TO THE CLIENT SITE (BALANCE UPDATE DEBIT and CREDIT)
									$guzzle_response = $client->post($client_details->fund_transfer_url,
									// $guzzle_response = $client->post('127.0.0.1:8000/api/fundtransferrequest',
									    ['body' => json_encode(
									        	[
												  "access_token" => $client_details->client_access_token,
												  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
												  "type" => "fundtransferrequest",
												  "datetsent" => Helper::datesent(),
												  "gamedetails" => [
												    "gameid" => $db_game_code,
												    "gamename" => $db_game_name
												  ],
												  "fundtransferrequest" => [
														"playerinfo" => [
														"client_player_id" => $client_details->client_player_id,
														"token" => $client_details->player_token
													],
													"fundinfo" => [
													      "gamesessionid" => "",
													      "transactiontype" => $transaction_type,
													      "transferid" => "",
													      "rollback" => "false",
													      "currencycode" => $client_details->default_currency,
													      "amount" => $pay_amount // Amount to be send!
													]
												  ]
												]
									    )]
									);

								    $client_response = json_decode($guzzle_response->getBody()->getContents());
									// Helper::saveLog('WalletCostTransfer', 2, json_encode($client_response), 'demoRes');
									Helper::saveLog('BOLE WALLET CALL TRANSFER', $this->provider_db_id, $request->getContent(), json_encode($client_response));

									$get_balance = Helper::getBalance($client_details); // TEST

									$data = [
										"data" => [
											"balance" => floatval(number_format((float)$get_balance, 2, '.', '')), 
											"currency" => $client_details->default_currency,
										],
										"status" => [
											"code" => 0,
											"msg" => "success"
										]
									];

								}
				                catch(ClientException $e){
				                  $client_response = $e->getResponse();
				                  $response = json_decode($client_response->getBody()->getContents(),True);
				                  return response($response,$client_response->getStatusCode())
				                   ->header('Content-Type', 'application/json');
				                }


				        }else{
					            try
								{	

										if($json_data->game_code == 'slot'){
									    	// $game_details = Game::find($json_data->game_code.'_'.$json_data->cost_info->scene);
									    	// $game_details = Game::find($json_data->game_code.'_'.$json_data->cost_info->scene);
									    	$db_game_name = "slot";
											$db_game_code = "slot";
									    }else{
									    	$game_details = Game::find($json_data->game_code);
									    	$db_game_name = $game_details->game_name;
											$db_game_code = $game_details->game_code;
									    }
										
										$pay_amount = $json_data->amount;
										// THIS GAME DONT HAVE BUY IN DATA!
										if($json_data->game_code == 'blackjack' || 
										   $json_data->game_code == 'ermj' || 
										   $json_data->game_code == 'gyzjmj' || 
										   $json_data->game_code == 'hbmj' || 
										   $json_data->game_code == 'hzmj' || 
										   $json_data->game_code == 'hnmj' || 
										   $json_data->game_code == 'gdmj' || 
										   $json_data->game_code == 'dzmj' || 
										   $json_data->game_code == 'zjh' || 
										   $json_data->game_code == 'sangong' || 
										   $json_data->game_code == 'tbnn' || 
								           $json_data->game_code == 'qydz' || 
										   $json_data->game_code == 'blnn' || 
										   $json_data->game_code == 'mjxzdd' || 
										   $json_data->game_code == 'mjxlch'){ 

											// 073020
										 //   	$client_response = Providerhelper::playerDetailsCall($client_details->player_token);
											// $data = [
											// 	"data" => [
											// 		"balance" => floatval(number_format((float)$client_response->playerdetailsresponse->balance, 2, '.', '')),
											// 		"currency" => $client_details->default_currency,
											// 	],
											// 	"status" => [
											// 		"code" => 0,
											// 		"msg" => "success"
											// 	]
											// ];

											// return $data;
											// END 073020
											$pay_amount = 0;
										}
				               
				               			// Multi Games / Baccarat and rbwar = payamount is their amount
										// Game Buy In if COST_INFO has no data always Debit!!
										$guzzle_response = $client->post($client_details->fund_transfer_url,
										// $guzzle_response = $client->post('127.0.0.1:8000/api/fundtransferrequest',
										    ['body' => json_encode(
										        	[
													  "access_token" => $client_details->client_access_token,
													  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
													  "type" => "fundtransferrequest",
													  "datesent" => Helper::datesent(),
													  "gamedetails" => [
														  "gameid" => $db_game_code,
												   		  "gamename" => $db_game_name
													  ],
													  "fundtransferrequest" => [
															"playerinfo" => [
															"client_player_id" => $client_details->client_player_id,
															"token" => $client_details->player_token
														],
														"fundinfo" => [
														      "gamesessionid" => "",
														      "transactiontype" => 'debit', // Game Buy In Debit
														      "transferid" => "",
														      "rollback" => "false",
														      "currencycode" => $client_details->default_currency,
														      "amount" => $pay_amount // Amount!
														]
													  ]
													]
										    )]
										);

										    $client_response = json_decode($guzzle_response->getBody()->getContents());
											// Helper::saveLog('GAME_BUY_IN', 2, json_encode($client_response), 'demoRes');
											Helper::saveLog('BOLE WALLET CALL GBI', 2, $request->getContent(), json_encode($client_response));

											$data = [
												"data" => [
													"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
													"currency" => $client_details->default_currency,
												],
												"status" => [
													"code" => 0,
													"msg" => "success"
												]
											];
								
								}
				                catch(ClientException $e)
				                {
				                  $client_response = $e->getResponse();
				                  $response = json_decode($client_response->getBody()->getContents(),True);
				                  return response($response,$client_response->getStatusCode())
				                   ->header('Content-Type', 'application/json');
				                }
					    }
			}

			return $data;

		}




		public function playerWalletBalance(Request $request)
		{
			// Helper::saveLog('BOLE WALLET BALANCE', $this->provider_db_id, $request->getContent(), 'TEST');
			$json_data = json_decode($request->getContent());
			$client_details = ProviderHelper::getClientDetails('player_id', $json_data->player_account);
			$this->changeConfig('player_id', $client_details->player_id);
			$hashen = $this->chashen($json_data->operator_id, $json_data->player_account, $json_data->sha1);
			if(!$hashen){
	            $data = [
					"resp_msg" => [
						"code" => 43006,
						"message" => 'signature error',
						"errors" => []
					]
				];
		        Helper::saveLog('BOLE UNKNOWN CALL', $this->provider_db_id, $request->getContent(), 'UnknownboleReq');
				return $data;
			}
			$client_details = Providerhelper::getClientDetails('player_id', $json_data->player_account);
			if($client_details != null)
			{
				$client_response = Providerhelper::playerDetailsCall($client_details->player_token);
				$data = [
					"data" => [
						"balance" => floatval(number_format((float)$client_response->playerdetailsresponse->balance, 2, '.', '')),
						"currency" => $client_details->default_currency,
					],
					"status" => [
						"code" => 0,
						"msg" => "success"
					]
				];
			}else{
				$data = [
					"resp_msg" => [
						"code" => 43101,
						"message" => 'the user does not exist',
						"errors" => []
					]
				];
			}
			// Helper::saveLog('BOLE WALLET BALANCE', $this->provider_db_id, $request->getContent(), $data);
			return $data;
		}

	    // BACKUP FUNCTION
	    // public static function find($game_code) {
		// 	$search_result = DB::table('games')
		// 							->where('game_code', $game_code)
		// 							->first();	
		// 	return ($search_result ? $search_result : false);
		// }

		// public static function findbyid($game_id) {
		// 	$search_result = DB::table('games')
		// 							->where('game_id', $game_id)
		// 							->first();	
		// 	return ($search_result ? $search_result : false);
		// }

}
