<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
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

		 // private $AccessKeyId ="9048dbaa-b489-4b32-9a29-149240a5cefe"; // Bole Access id
   		 // private $access_key_secret = "4A55C539E93B189EAA5A76A8BD92B99B87B76B80"; // Bole Secret
  		 // private $app_key = 'R14NDR4FT'; // Wallet App Key

   		 // public $AccessKeyId = config('providerlinks.bolegaming.AccessKeyId'); // Bole Access id
   		 // public $access_key_secret = config('providerlinks.bolegaming.access_key_secret'); // Bole Secret
   		 // public $app_key = config('providerlinks.bolegaming.app_key'); // Wallet App Key

   		public  $AccessKeyId, $access_key_secret, $app_key, $login_url, $logout_url;

	    public function __construct()
		{
		    $this->AccessKeyId = config('providerlinks.bolegaming.AccessKeyId');
		    $this->access_key_secret = config('providerlinks.bolegaming.access_key_secret');
   		    $this->app_key = config('providerlinks.bolegaming.app_key');
   		    $this->login_url = config('providerlinks.bolegaming.login_url');
   		    $this->logout_url = config('providerlinks.bolegaming.logout_url');
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
		 *  DEPRECATED CENTRALIZED
		 *  register the client player 
		 *  Require Request
		 *	player token, game_code, merchant_user 
		 */	
		public function playerRegister(Request $request)
		{
			$json_data = json_decode(file_get_contents("php://input"), true);
			Helper::saveLog('BOLE REGISTER', 11, file_get_contents("php://input"), 'DEMO CALL');

			/* CHECK CLIENT iF EXIST REGISTER PLAYER TOKEN IF NOT CLIENT GO HOME! */	
			// $check_client = $this->checkClientPlayer($request->site_url, $request->merchant_user, $request->token);
			$check_client = $this->checkClientPlayer($json_data['site_url'], 
													$json_data['playerdetailsrequest']['username'], 
													$json_data['playerdetailsrequest']['token']);
			if($check_client['httpstatus'] != 200){
				return $check_client;
			}
			/* END CHECK CLIENT */

			 $sign = $this->generateSign();

			 $client_details = $this->_getClientDetails('token', $json_data['playerdetailsrequest']['token']);


			 $response = [
			 	"errorcode" =>  "CLIENT_NOT_FOUND",
				"errormessage" => "Client not found",
				"httpstatus" => "404"
			 ];


			 if ($client_details) {
			 	// return $client_details->client_id;
			 	// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 11, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
							"httpstatus" => "404"
						];
				}
				else
				{

					$client = new Client([
					    'headers' => [ 
					    	'Content-Type' => 'application/json',
					    	'Authorization' => 'Bearer '.$client_details->client_access_token
					    ]
					]);
					
					$guzzle_response = $client->post($client_details->player_details_url,
					    ['body' => json_encode(
					        	["access_token" => $client_details->client_access_token,
									"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									"type" => "playerdetailsrequest",
									"datesent" => Helper::datesent(),
									"gameid" => "",
									"clientid" => $client_details->client_id,
									"playerdetailsrequest" => [
										"client_player_id" => $client_details->client_player_id,
										"token" =>$json_data['playerdetailsrequest']['token'],
										"gamelaunch" => true,
									    "refreshtoken" => false
									]]
					    )]
					);

					$client_response = json_decode($guzzle_response->getBody()->getContents());

					
					if(isset($client_response->playerdetailsresponse->status->code) 
						&& $client_response->playerdetailsresponse->status->code == "200") {

							 $http = new Client();
					         // $response = $http->post('https://api.cdmolo.com:16800/v1/player/login', [
					         // $response = $http->post(config('providerlinks.bolegaming.login_url'), [
					         $response = $http->post($this->login_url, [
					            'form_params' => [
					                'game_code' => $json_data['gamecode'],
					                'scene' => '',
					                'player_account' => $client_response->playerdetailsresponse->username,
					                'country'=> $client_response->playerdetailsresponse->country_code,
					                'ip'=> $_SERVER['REMOTE_ADDR'],
					                'AccessKeyId'=> $this->AccessKeyId,
					                'Timestamp'=> $sign['timestamp'],
					                'Nonce'=> $sign['nonce'],
					                'Sign'=> $sign['signature'],
					                //'op_pay_url' => 'http://middleware.freebetrnk.com/public/api/bole/wallet',
					                'op_race_return_type' => 1, // back to previous game
					                'op_return_type' => 3, //hide home button for games test
					                //'op_home_url' => 'https://demo.freebetrnk.com/casino', //hide home button for games test
					                'ui_hot_list_disable' => 1, //hide latest game menu
					                'ui_category_disable' => 1 //hide category list
					            ],
					         ]);

					        $response = $response->getBody()->getContents();

					}

				}


				
			}


	         Helper::saveLog('BOLE REGISTER', 11, $response, 'resBoleReg');
	         return $response;
		}


		/**
		 *  NOT USED!
		 *  Logout the player
		 */	
		public function playerLogout(Request $request)
		{

			 Helper::saveLog('BOLE_LOGOUT', 11, 'logouted', 'BOLE CALL');
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
			// Helper::saveLog('WALLET CALL BOLE', 11, '11', 'BOLE CALL');
			Helper::saveLog('BOLE WALLET CALL', 2, $request->getContent(), 'boleReq');
			$hashen = $this->chashen($json_data->operator_id, $json_data->player_account, $json_data->sha1);
			if(!$hashen){
		        return ["code" => "error"];
		        Helper::saveLog('BOLE UNKNOWN CALL', 11, $request->getContent(), 'UnknownboleReq');
			}


			$client_details = $this->_getClientDetails('player_id', $json_data->player_account);
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
												    "gameid" => $json_data->game_code,
												    "gamename" => ""
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
													      "currencycode" => $client_details->currency,
													      "amount" => $pay_amount // Amount to be send!
													]
												  ]
												]
									    )]
									);

								    $client_response = json_decode($guzzle_response->getBody()->getContents());
									// Helper::saveLog('WalletCostTransfer', 2, json_encode($client_response), 'demoRes');
									Helper::saveLog('BOLE WALLET CALL TRANSFER', 11, $request->getContent(), json_encode($client_response));

									$get_balance = Helper::getBalance($client_details); // TEST

									$data = [
										"data" => [
											"balance" => floatval(number_format((float)$get_balance, 2, '.', '')), // TEST
											// "balance" => number_format((float)$client_response->fundtransferresponse->balance, 2, '.', ''),
											"currency" => $client_response->fundtransferresponse->currencycode,
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

												$pay_amount = 0;
										}
				               

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
													    "gameid" => $json_data->game_code,
													    "gamename" => ""
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
														      "currencycode" => $client_details->currency,
														      "amount" => $pay_amount // Amount!
														]
													  ]
													]
										    )]
										);

										    $client_response = json_decode($guzzle_response->getBody()->getContents());
											// Helper::saveLog('GAME_BUY_IN', 2, json_encode($client_response), 'demoRes');
											Helper::saveLog('BOLE WALLET CALL GAME_BUY_IN', 2, $request->getContent(), json_encode($client_response));

											$data = [
												"data" => [
													"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
													"currency" => $client_response->fundtransferresponse->currencycode,
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
			Helper::saveLog('BOLE WALLET BALANCE', 11, $request->getContent(), 'TEST');
			$json_data = json_decode($request->getContent());
			// dd($json_data->player_account);

			// $hashen = $this->chashen($request->operator_id, $request->player_account, $request->sha1);
			$hashen = $this->chashen($json_data->operator_id, $json_data->player_account, $json_data->sha1);
			// if(!$hashen){
		    //        return ["code" => "error"];
		    //        Helper::saveLog('UnknownCall', 11, $request->getContent(), 'UnknownboleReq');
			// }
			// $client_details = $this->_getClientDetails('player_id', $request->player_account);
			$client_details = $this->_getClientDetails('player_id', $json_data->player_account);
			// dd($client_details);
			if($client_details)
			{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);

				try
				{	
					$guzzle_response = $client->post($client_details->player_details_url,
					    ['body' => json_encode(
					        	["access_token" => $client_details->client_access_token,
									"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									"type" => "playerdetailsrequest",
									"datesent" => Helper::datesent(),
									"gameid" => "",
									"clientid" => $client_details->client_id,
									"playerdetailsrequest" => [
										"client_player_id" => $client_details->client_player_id,
										"token" => $client_details->player_token,
										"gamelaunch" => true,
										"refreshtoken" => false
									]
								]
					    )]
					);

					$client_response = json_decode($guzzle_response->getBody()->getContents());
					// dd($client_response);
				}
                catch(ClientException $e)
                {
                  $client_response = $e->getResponse();
                  $response = json_decode($client_response->getBody()->getContents(),True);
                  return response($response,$client_response->getStatusCode())
                   ->header('Content-Type', 'application/json');
                }	
			}

			$data = [
				"data" => [
					"balance" => floatval(number_format((float)$client_response->playerdetailsresponse->balance, 2, '.', '')),
					"currency" => $client_response->playerdetailsresponse->currencycode,
				],
				"status" => [
					"code" => 0,
					"msg" => "success"
				]
			];

			Helper::saveLog('BOLE WALLET BALANCE', 11, $request->getContent(), $data);

			return $data;

		}


		/**
		 * DEPRECATED CENTRALIZED!
		 * Check Player Using Token if its already register in the MW database if not register it!
		 */
		public function checkClientPlayer($site_url, $merchant_user ,$token = false)
		{

				// Check Client Server Name
				$client_check = DB::table('clients')
	          	 	 ->where('client_url', $site_url)
	           		 ->first();

	           	$data = [
		        	"msg" => "Client Not Found",
		        	"httpstatus" => "404"
		        ];  	 

	            if($client_check){  

		                $player_check = DB::table('players')
		                    ->where('client_id', $client_check->client_id)
		                    ->where('username', $merchant_user)
		                    ->first();

		                if($player_check){

		                    DB::table('player_session_tokens')->insert(
		                            array('player_id' => $player_check->player_id, 
	                            		  'player_token' =>  $token, 
		                            	  'status_id' => '1')
		                    );    

		                    $token_player_id = $this->_getPlayerTokenId($player_check->player_id);

		                    $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => false
					        ];   

		                }else{

	                	try
	                	{
						        $client_details = $this->_getClientDetails('site_url', $site_url);

								$client = new Client([
								    'headers' => [ 
								    	'Content-Type' => 'application/json',
								    	'Authorization' => 'Bearer '.$client_details->client_access_token
								    ]
								]);

								$guzzle_response = $client->post($client_details->player_details_url,
								    ['body' => json_encode(
								        	["access_token" => $client_details->client_access_token,
												"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
												"type" => "playerdetailsrequest",
												"datesent" => Helper::datesent(),
												"gameid" => "",
												"clientid" => $client_details->client_id,
												"playerdetailsrequest" => [
													"client_player_id" => $client_details->client_player_id,
													"token" => $token,
													"gamelaunch" => true,
													"refreshtoken" => false,
												]]
								    )]
								);

								$client_response = json_decode($guzzle_response->getBody()->getContents());

								DB::table('players')->insert(
		                            array('client_id' => $client_check->client_id, 
		                            	  'client_player_id' =>  $client_response->playerdetailsresponse->accountid, 
		                            	  'username' => $client_response->playerdetailsresponse->username, 
		                            	  'email' => $client_response->playerdetailsresponse->email,
		                            	  'display_name' => $client_response->playerdetailsresponse->accountname)
			                    );

			                	$last_player_id = DB::getPDO()->lastInsertId();

			                	DB::table('player_session_tokens')->insert(
				                            array('player_id' => $last_player_id, 
				                            	  'player_token' =>  $token, 
				                            	  'status_id' => '1')
			                    );

			                	$token_player_id = $this->_getPlayerTokenId($last_player_id);

						}
						catch(ClientException $e)
						{
						  $client_response = $e->getResponse();
						  $response = json_decode($client_response->getBody()->getContents(),True);
						  return response($response,$client_response->getStatusCode())
						   ->header('Content-Type', 'application/json');
						}

				                $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => true
						        ];   

		      			}     

				}

		        return $data;
			

		}



		public function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.client_player_id','p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'c.default_currency', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
					 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
					 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
					 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
					 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
					 
					if ($type == 'token') {
						$query->where([
					 		["pst.player_token", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}

					if ($type == 'player_id') {
						$query->where([
					 		["p.player_id", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}


					if ($type == 'site_url') {
						$query->where([
					 		["c.client_url", "=", $value],
					 	]);
					}

					if ($type == 'username') {
						$query->where([
					 		["p.username", $value],
					 	]);
					}

					 $result= $query
					 			->latest('token_id')
					 			->first();

			return $result;

		}


		public function _getPlayerTokenId($player_id){

	       $client_details = DB::table("players AS p")
	                         ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id','pst.token_id' , 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
	                         ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
	                         ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
	                         ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
	                         ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
	                         ->where("p.player_id", $player_id)
	                         ->where("pst.status_id", 1)
	                         ->latest('token_id')
	                         ->first();

	        return $client_details->token_id;    
	        
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
