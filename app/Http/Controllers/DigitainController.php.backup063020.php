<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\GameRound;
use App\Helpers\GameTransaction;
use App\Helpers\Helper;
use App\Helpers\CallParameters;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;

/**
 *  UPDATED 06-27-20 BACKUP 6-30-20
 *	Api Documentation v3 -> v3.7.0-1
 *	Current State : v3 updating to v3.7.0-1 
 *	@author [rian] <[<riandraft@gmail.com>]> IF ANY QUESTION FEEL FREE TO PING ME UP!  
 *  @author's NOTE: You cannot win if you dont bet! Bet comes first fellows!
 *	@author's NOTE: roundId is intentionally PREFIXED with RSG to separate from other roundid, safety first!
 *	@method refund method additionals = requests: holdEarlyRefund
 *	@method win method additionals = requests:  returnBetsAmount, bonusTicketId
 *	@method bet method additionals = requests:  checkRefunded, bonusTicketId
 *	@method betwin method additionals = requests:  bonusTicketId,   ,response: playerId, roundId, currencyId
 *	
 */
class DigitainController extends Controller
{
    private $digitain_key = "BetRNK3184223";
    private $operator_id = 'B9EC7C0A';

    /**
	 *	Verify Signature
	 *	@return  [Bolean = True/False]
	 *
	 */
	public function authMethod($operatorId, $timestamp, $signature){
		$digitain_key = "BetRNK3184223";
	    $operator_id = $operatorId;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
		$result = false;
            if($hmac == $signature) {
			    $result = true;
            }
        return $result;
	}

	/**
	 *	Create Signature
	 *	@return  [String]
	 *
	 */
	public function createSignature($timestamp){
	    $digitain_key = $this->digitain_key;
	    $operator_id = $this->operator_id;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
	    return $hmac;
	}

	/**
	 * Player Detail Request
	 * @return array [Client Player Data]
	 * 
	 */
    public function authenticate(Request $request)
    {	
		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('Authentication RSG', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"token" => $json_data['token'],
			"errorCode" => 12 //Wrong Operator Id 
		];
		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):

	   		$client_details = $this->_getClientDetails('token', $json_data["token"]);	
			if ($client_details):

				$client_response = $this->playerDetailsCall($json_data["token"]);
				if($client_response):
					if(isset($client_response->playerdetailsresponse->status->code) &&
						     $client_response->playerdetailsresponse->status->code == "200"):
						$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							"errorCode" => 1,
							"playerId" => $client_details->player_id, // Player ID Here is Player ID in The MW DB, not the client!
							"userName" => $client_response->playerdetailsresponse->accountname,
							// "currencyId" => $client_response->playerdetailsresponse->currencycode,
							"currencyId" => $client_details->default_currency,
							"balance" => $client_response->playerdetailsresponse->balance,
							"birthDate" => '', // Optional
							"firstName" => $client_response->playerdetailsresponse->firstname, // required
							"lastName" => $client_response->playerdetailsresponse->lastname, // required
							"gender" => '', // Optional
							"email" => $client_response->playerdetailsresponse->email,
							"isReal" => true
						];
					endif;
				else:
					$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							// "token" => $json_data['token'],
							"errorCode" => 999, // client cannot be reached! http errors etc!
					];
				endif;
			else:
				$response = [
					"timestamp" => date('YmdHisms'),
					"signature" => $this->createSignature(date('YmdHisms')),
					"token" => $json_data['token'],
					"errorCode" => 2 //Wrong Token
				];
			endif;
		endif;

		Helper::saveLog('Authentication RSG', 2, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 * Get the player balance
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 16 = invalid currency type, 999 = general error (HTTP)]
	 * @return  [<json>]
	 * 
	 */
	public function getBalance()
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('RSG Player Balance', 2, file_get_contents("php://input"), 'ENDPOINT HIT');

		$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"errorCode" => 12,
					];

		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
			$client_details = $this->_getClientDetails('token', $json_data["token"]);	
			if ($client_details):

				$client_response = $this->playerDetailsCall($json_data["token"]);

				if($client_response):
					// if($json_data["currencyId"] == $client_response->playerdetailsresponse->currencycode):
					if($json_data["currencyId"] == $client_details->default_currency):
						$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							"errorCode" => 1,
							"balance" => $client_response->playerdetailsresponse->balance,
						];
					else:
						$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							"token" => $json_data['token'],
							"errorCode" => 16, // Error Currency type
						];
					endif;
				else:
					$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							// "token" => $json_data['token'],
							"errorCode" => 999, // Http error
					];
				endif;

			endif;
				Helper::saveLog('PLAYER BALANCE RSG', 2, file_get_contents("php://input"), $response);
				return json_encode($response);
		else:
			Helper::saveLog('PLAYER BALANCE RSG', 2, file_get_contents("php://input"), $response);
			return json_encode($response);
		endif;
	}


	/**
	 * Call if Digitain wants a new token!
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 999 = general error (HTTP)]
	 * @return  [<json>]
	 * 
	 */
	public function refreshtoken(){
		Helper::saveLog('Auth Refresh Token RSG', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"token" => $json_data['token'],
			"errorCode" => 12 //Wrong Operator Id 
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
			$client_details = $this->_getClientDetails('token', $json_data['token']);

			if($client_details):
			 	
			 	if($json_data['changeToken']): // IF TRUE REQUEST ADD NEW TOKEN

					$client_response = $this->playerDetailsCall($json_data["token"], true);
					if($client_response):
						DB::table('player_session_tokens')->insert(
		                            array('player_id' => $client_details->player_id, 
		                            	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
		                            	  'status_id' => '1')
		                );
						$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							"token" => $client_response->playerdetailsresponse->refreshtoken, // Return New Token!
							"errorCode" => 1
						];
					else:
						$response = [
							"timestamp" => date('YmdHisms'),
							"signature" => $this->createSignature(date('YmdHisms')),
							// "token" => $json_data['token'],
							"errorCode" => 999,
						];
					endif;

			 	else:
			 		$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"token" => $json_data['token'], // Return OLD Token
						"errorCode" => 1
					];
			 	endif;
			else:
			 	$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"token" => $json_data['token'],
						"errorCode" => 12,
				];
			endif;
		endif;
		Helper::saveLog('Auth Refresh Token RSG', 14, file_get_contents("php://input"), $response);
		return $response;
	}
	
	/**
	 * @author's NOTE:
	 * allOrNone - When True, if any of the items fail, the Partner should reject all items NO LOGIC YET!
	 * checkRefunded - no logic yet
	 * ignoreExpiry - no logic yet, expiry should be handle in the refreshToken call
	 * changeBalance - no yet implemented always true (RSG SIDE)
	 * UPDATE 4 filters - Player Low Balance, Currency code dont match, already exist, The playerId was not found
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 6 = Player Low Balance!, 16 = Currency code dont match, 999 = general error (HTTP), 8 = already exist, 4 = The playerId was not found]
	 * 
	 */
	public function bet(Request $request){
		Helper::saveLog('RSG BET GAME REQUEST FIRST', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 12 //Wrong Operator Id 
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):

			$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
			$items_array = array(); // ITEMS INFO
		 	foreach ($json_data['items'] as $key):

		 		$client_details = $this->_getClientDetails('token', $key['token']);
		 		if(!empty($client_details)):

		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
		 		if(!$check_win_exist): // No Bet Exist!
		 		if($key['currencyId'] == $client_details->default_currency): // Currency not match

		 		$client_player = $this->playerDetailsCall($key['token']);
		 		if($client_player): // If client side failed to reply
		 		if($client_player->playerdetailsresponse->balance > $key['betAmount']): // Player balance is low!

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
					  "datetsent" => "",
					  "gamedetails" => [
					    "gameid" =>  $key['gameId'],
					    "gamename" => ""
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"token" => $key['token'],
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "rollback" => "false",
						      "currencycode" => $client_details->currency,
						      "amount" => $key['betAmount']
						]
					  ]
					];

					try {

						$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

						$client_response = json_decode($guzzle_response->getBody()->getContents());

				 		$payout_reason = 'Bet : '.$this->getOperationType($key['operationType']);
				 		$win_or_lost = 0;
				 		$method = 1;
				 	    $token_id = $client_details->token_id;
				 	    if(isset($key['roundId'])){
				 	    	$round_id = 'RSG'.$key['roundId'];
				 	    }else{
				 	    	$round_id = 1;
				 	    }

				 	    if(isset($key['txId'])){
				 	    	$provider_trans_id = $key['txId'];
				 	    }else{
				 	    	$provider_trans_id = null;
				 	    }
				 	    $game_details = Helper::findGameDetails('game_code', 14, $key['gameId']);	
				 	    $bet_payout = 0; // Bet always 0 payout!

				 	    $income = $key['betAmount'] - $bet_payout;
				 		$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $key['betAmount'],  $bet_payout, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
				   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response, $json_data, 1, $key['betAmount'], $key['txId'] ,$key['roundId']);

		        	    $items_array[] = [
		        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
							 "balance" => $client_response->fundtransferresponse->balance,
							 "info" => $key['info'], // Info from RSG, MW Should Return it back!
							 "errorCode" => 1, // No Problem
							 "metadata" => "" // Optional but must be here!
		        	    ];  

		        	    #STORE THE SUCCESSFULL CALL
				 		#ALLORNONE STORE DATA FOR A REVERSE CALLBACK IF ONE OF ITEM FAILED
						$items_allOrNone[] = [
							'header' => $client_details->client_access_token,
							'url' => $client_details->fund_transfer_url,
							'body' => $this->reverseDataBody($requesttosend),
						];
						#ALLORNONE END

					} catch (\Exception $e) {
						// IF ALL OR NONE IS TRUE IF ONE ITEM FAILED BREAK THE FLOW!!
						if($json_data['allOrNone'] == 'true'):
							$this->megaRollback($items_allOrNone, $json_data); // ROLBACK THE ALREADY SEND ITEMS!
					        return 	$response = array(
										 "timestamp" => date('YmdHisms'),
									     "signature" => $this->createSignature(date('YmdHisms')),
										 "errorCode" => 999,
										 // "info" => $key['info'],
						   			);
						else:
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 999, // Http Failed!
								 "metadata" => "" // Optional but must be here!
			        	    ]; 
						endif;	
					}

	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 6, // Player Low Balance!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 999, // Client Side Failed to response!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif; 
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 16, // Currency code dont match!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;         
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, // already exist
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif; 
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 4, //The playerId was not found
						 "metadata" => "" // Optional but must be here!
	        	    ];  
	        	endif;    
			endforeach;
				$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
	   			);				
		endif;
			Helper::saveLog('RSG BET GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}



	/**
	 *	
	 * @author's NOTE
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 999 = general error (HTTP), 8 = already exist, 16 = error currency code]	
	 *
	 */
	public function win(Request $request){
			
		// Helper::saveLog('WIN RSG REQUESTED', 14, 'LOGS', 'LOGS');
		Helper::saveLog('RSG WIN GAME REQUEST FIRST', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 12 //Wrong Operator Id 
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):		

			$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
			$items_array = array(); // ITEMS INFO
			foreach ($json_data['items'] as $key):

		 		$client_details = $this->_getClientDetails('player_id', $key['playerId']);
	 			if(!empty($client_details)):

		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
	 			if(!$check_win_exist):

	 			$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'],2);
	 			if(!$checkLog):
	 				
	 			if($key['currencyId'] == $client_details->default_currency): // Currency not match nb //

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
						  "datetsent" => "",
						  "gamedetails" => [
						    "gameid" =>  $key['gameId'],
						    "gamename" => ""
						  ],
						  "fundtransferrequest" => [
								"playerinfo" => [
								"token" => $client_details->player_token,
								"playerId" => $key['playerId']
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => 'credit',
							      "rollback" => "false",
							      "currencycode" => $client_details->currency, // This data was pulled from the client
							      "amount" => $key['winAmount']
							]
						  ]
						];

				 		try {

				 		$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

				 		$client_response = json_decode($guzzle_response->getBody()->getContents());
				 		// dd($client_response);
				 		// TEST GAME TRANSACTION LOGGING
				 		$payout_reason = 'Win : '.$this->getOperationType($key['operationType']);
				 		$win_or_lost = 1;
				 		$method = 2;
				 		
				 	    $token_id = $client_details->token_id;
				 	    if(isset($key['roundId'])){
				 	    	$round_id = 'RSG'.$key['roundId'];
				 	    }
				 	    // elseif(isset($key['betTxId'])){
				 	    // 	$round_id = 'RSG'.$key['betTxId']; // SCENARIO
				 	    // }
				 	    else{
				 	    	$round_id = 1;
				 	    }

				 	    if(isset($key['txId'])){
				 	    	$provider_trans_id = $key['txId'];
				 	    }else{
				 	    	$provider_trans_id = null;
				 	    }

				 	    // NEW BASIS GAME_TRANSACTION BET_AMOUNT!
						if(isset($key['betTxId'])){
	        	    		$bet_transaction_detail = $this->findGameTransaction($key['betTxId']);
	        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
	        	    	}else{
	        	    		$bet_transaction_detail = $this->findPlayerGameTransaction('RSG'.$key['roundId'], $key['playerId']);
	        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
	        	    	}
				 			$income = $bet_transaction - $key['winAmount']; // Sample	
				 	  		$game_details = Helper::findGameDetails('game_code', 14, $key['gameId']);

				 	  		// NO MORE WIN LOGS! 06-25-2020			
				 	  		// $game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $bet_transaction,  $key['winAmount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

				 	  		// WIN LOGS TO UPDATE!
				 	  	    // $update_bet = $this->updateBetToWin($key['roundId'], $key['winAmount'], $key['winAmount'], $key['winAmount'], 2);

				 	  		// HEAD 06-25-2020
				 	  		if($key['winAmount'] != 0){
				 	  			if($bet_transaction_detail->bet_amount > $key['winAmount']){
				 	  				$win = 0; // lost
				 	  				$entry_id = 1; //lost
				 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
				 	  			}else{
				 	  				$win = 1; //win
				 	  				$entry_id = 2; //win
				 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
				 	  			}
			 	  				$updateTheBet = $this->updateBetToWin('RSG'.$key['roundId'], $key['winAmount'], $income, $win, $entry_id);
				 	  		}

				 			$rsg_trans_ext = $this->createRSGTransactionExt($bet_transaction_detail->game_trans_id, $json_data, $requesttosend, $client_response, $client_response,$json_data, 2, $key['winAmount'], $key['txId'] ,$key['roundId']);

			       
			        	    if(isset($key['returnBetsAmount']) && $key['returnBetsAmount'] == true): // SCENARIO
			        	    	if(isset($key['betTxId'])){
			        	    		$datatrans = $this->findTransactionRefund($key['betTxId'], 'transaction_id');
			        	    		// dd('betTxId');
			        	    	}else{
			        	    		// $datatrans = $this->findTransactionRefund('RSG'.$key['roundId'], 'bet');
			        	    		$datatrans = $this->findTransactionRefund('RSG'.$key['roundId'], 'round_id');
			        	    	}
			        	    	$gg = json_decode($datatrans->transaction_detail);
			        	    	// dd($gg);
						 		$total_bets = array();
						 		foreach ($gg->items as $gg_tem) {
									$total_bets[] = $gg_tem->betAmount;
						 		}
						 	
				        	    $items_array[] = [
				        	    	 "externalTxId" => $bet_transaction_detail->game_trans_id, // MW Game Transaction Id
									 "balance" => $client_response->fundtransferresponse->balance,
									 "betsAmount" => array_sum($total_bets),
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 1,
									 "metadata" => "", // Optional but must be here!
				        	    ];
				        	else:
		        		 	    $items_array[] = [
				        	    	 "externalTxId" => $bet_transaction_detail->game_trans_id, // MW Game Transaction Id
									 "balance" => $client_response->fundtransferresponse->balance,
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 1,
									 "metadata" => "", // Optional but must be here!
				        	    ];
			        	    endif;

			        	    #STORE THE SUCCESSFULL CALL
					 		#ALLORNONE STORE DATA FOR A REVERSE CALLBACK IF ONE OF ITEM FAILED
							$items_allOrNone[] = [
								'header' => $client_details->client_access_token,
								'url' => $client_details->fund_transfer_url,
								'body' => $this->reverseDataBody($requesttosend),
							];
							#ALLORNONE END

				 		}catch (\Exception $e){
				 			// IF ALL OR NONE IS TRUE IF ONE ITEM FAILED BREAK THE FLOW!!
							if($json_data['allOrNone'] == 'true'):
								$this->megaRollback($items_allOrNone, $json_data); // ROLBACK THE ALREADY SEND ITEMS!
						        return 	$response = array(
											 "timestamp" => date('YmdHisms'),
										     "signature" => $this->createSignature(date('YmdHisms')),
											 "errorCode" => 999,
											 // "info" => $key['info'],
							   			);
							else:
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 999, // Http Failed!
									 "metadata" => "" // Optional but must be here!
				        	    ]; 
							endif;
				 		}
				 		
				else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 16, // Currency code dont match!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;  
			    else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;      	    
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 4, //The playerId was not found
						 "metadata" => "" // Optional but must be here!
	        	    ];  
	        	endif;    
			endforeach;
        	    $response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
	   			);	
		endif;

			Helper::saveLog('RSG WIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}


	/**
	 *	
	 * NOTE
	 * Accept Bet and Win At The Same Time!
	 */
	public function betwin(Request $request){

		Helper::saveLog('RSG BETWIN GAME REQUEST FIRST', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 12 //Wrong Operator Id 
		];

		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
			$items_array = array();
		 	foreach ($json_data['items'] as $key):

		 		$client_details = $this->_getClientDetails('token', $key['token']);

		 		if(!empty($client_details)): // if client is not found!

		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
		 		if(!$check_win_exist): // No Bet Exist!
		 		if($key['currencyId'] == $client_details->default_currency): // Currency not match

		 		$client_player = $this->playerDetailsCall($key['token']);
		 		if($client_player): // If client side failed to reply

		 		if($client_player->playerdetailsresponse->balance > $key['betAmount']): // Player balance is low!

		 			try {

	 					$client = new Client([
		                    'headers' => [ 
		                        'Content-Type' => 'application/json',
		                        'Authorization' => 'Bearer '.$client_details->client_access_token
		                    ]
		                ]);
				 		// First Call For The Bet
						$requesttosend = [
						  "access_token" => $client_details->client_access_token,
						  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
						  "type" => "fundtransferrequest",
						  "datetsent" => "",
						  "gamedetails" => [
						    "gameid" =>  $key['gameId'],
						    "gamename" => ""
						  ],
						  "fundtransferrequest" => [
								"playerinfo" => [
								"token" => $client_details->player_token,
								"playerId" => $key['playerId']
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => 'debit',
							      "rollback" => "false",
							      "currencycode" => $client_details->currency, // This data was pulled from the client
							      "amount" => $key['betAmount']
							]
						  ]
						];
						$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

				 		$client_response = json_decode($guzzle_response->getBody()->getContents());
				 		// TEST GAME TRANSACTION LOGGING
				 		$payout_reason = 'Bet : '.$this->getOperationType($key['betOperationType']);
				 		$win_or_lost = 0;
				 		$method = 1;
				 		$income = null; // Sample
				 	    $token_id = $client_details->token_id;
				 	    if(isset($key['roundId'])){
				 	    	$round_id = 'RSG'.$key['roundId'];
				 	    }else{
				 	    	$round_id = 1;
				 	    }

				 	    if(isset($key['txId'])){
				 	    	$provider_trans_id = $key['txId'];
				 	    }else{
				 	    	$provider_trans_id = null;
				 	    }

				 		$game_trans = Helper::saveGame_transaction($token_id, $key['gameId'], $key['betAmount'],  $key['betAmount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

				   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,$json_data, 1, $key['betAmount'], $key['txId'] ,$key['roundId']);

				   		// For The Win
						$requesttosend = [
						  "access_token" => $client_details->client_access_token,
						  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
						  "type" => "fundtransferrequest",
						  "datetsent" => "",
						  "gamedetails" => [
						    "gameid" =>  $key['gameId'],
						    "gamename" => ""
						  ],
						  "fundtransferrequest" => [
								"playerinfo" => [
								"token" => $client_details->player_token,
								"playerId" => $key['playerId']
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => 'credit',
							      "rollback" => "false",
							      "currencycode" => $client_details->currency, // This data was pulled from the client
							      "amount" => $key['winAmount']
							]
						  ]
						];
						$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

				 		$client_response_ii = json_decode($guzzle_response->getBody()->getContents());
				 		$payout_reason = 'Win : '.$this->getOperationType($key['winOperationType']);
				 		$win_or_lost = 1;
				 		$method = 2;
				 	    $token_id = $client_details->token_id;
				 	    if(isset($key['roundId'])){
				 	    	$round_id = 'RSG'.$key['roundId'];
				 	    }else{
				 	    	$round_id = 1;
				 	    }

				 	    if(isset($key['txId'])){
				 	    	$provider_trans_id = $key['txId'];
				 	    }else{
				 	    	$provider_trans_id = null;
				 	    }

				 	    if(isset($key['betTxId'])){
	        	    		$bet_transaction_detail = $this->findGameTransaction($key['betTxId']);
	        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
	        	    	}else{
	        	    		$bet_transaction_detail = $this->findPlayerGameTransaction('RSG'.$key['roundId'], $key['playerId']);
	        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
	        	    	}

				 	    $income = $bet_transaction - $key['winAmount']; // Sample	
			 	  		$game_details = Helper::findGameDetails('game_code', 14, $key['gameId']);
						if($key['winAmount'] != 0){
			 	  			if($bet_transaction_detail->bet_amount > $key['winAmount']){
			 	  				$win = 0; // lost
			 	  				$entry_id = 1; //lost
			 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
			 	  			}else{
			 	  				$win = 1; //win
			 	  				$entry_id = 2; //win
			 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
			 	  			}
			 	  				$updateTheBet = $this->updateBetToWin('RSG'.$key['roundId'], $key['winAmount'], $income, $win, $entry_id);
			 	  		}

	 					$rsg_trans_ext = $this->createRSGTransactionExt($bet_transaction_detail->game_trans_id, $json_data, $requesttosend, $client_response, $client_response,$json_data, 2, $key['winAmount'], $key['txId'] ,$key['roundId']);

		        	    $items_array[] = [
		        	    	 "externalTxId" => $game_trans, // MW Game Transaction Only Save The Last Game Transaction Which is the credit!
							 "balance" => $client_response_ii->fundtransferresponse->balance,
							 "betInfo" => $key['betInfo'], // Betinfo
							 "winInfo" => $key['winInfo'], // IWininfo
							 "errorCode" => 1,
							 "metadata" => "" // Optional but must be here!
		        	    ];
		 				
		 			} catch (\Exception $e) {
		 				return 	$response = array(
							 "timestamp" => date('YmdHisms'),
						     "signature" => $this->createSignature(date('YmdHisms')),
						     "betInfo" => $key['betInfo'], // Betinfo
							 "winInfo" => $key['winInfo'], // IWininfo
							 "errorCode" => 999,
			   			);
		 			}


		 		else:
        		$items_array[] = [
					 "betInfo" => $key['betInfo'], // Betinfo
					 "winInfo" => $key['winInfo'], // IWininfo
					 "errorCode" => 6, // Player Low Balance!
					 "metadata" => "" // Optional but must be here!
        	    ];   
	        	endif;
	        	else:
	        		$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 999, // Client Side Failed to response!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif; 
	        	else:
	        		$items_array[] = [
						"betInfo" => $key['betInfo'], // Betinfo
						"winInfo" => $key['winInfo'], // IWininfo
						"errorCode" => 16, // Currency code dont match!
						"metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;         
	        	else:
	        		$items_array[] = [
						"betInfo" => $key['betInfo'], // Betinfo
						"winInfo" => $key['winInfo'], // IWininfo
						"errorCode" => 8, // already exist
						"metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;
	        	else:
	        		$items_array[] = [
						"betInfo" => $key['betInfo'], // Betinfo
					    "winInfo" => $key['winInfo'], // IWininfo
						"errorCode" => 4, //The playerId was not found
						"metadata" => "" // Optional but must be here!
	        	    ];  
	        	endif; 

		 	endforeach;

	 		$response = array(
				 "timestamp" => date('YmdHisms'),
			     "signature" => $this->createSignature(date('YmdHisms')),
				 "errorCode" => 1,
				 "items" => $items_array,
   			);

		endif;
		Helper::saveLog('RSG BETWIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
		return $response;

	}

	/**
	 * UNDERCONSTRUCTION
	 * Refund Find Logs According to gameround, or TransactionID and refund whether it  a bet or win
	 *
	 * refundOriginalBet (No proper explanation on the doc!)	
	 * originalTxtId = either its winTxd or betTxd	
	 * refundround is true = always roundid	
	 * if roundid is missing always originalTxt, same if originaltxtid use roundId
	 *
	 */
	public function refund(Request $request){

		Helper::saveLog('RSG REFUND GAME REQUEST FIRST', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 12 //Wrong Operator Id 
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
		
	 		// CHECK REFUND IF ALREADY ARRIVED!
	 		// $refund_check = $this->findTransactionRefund('RSGREFUND'.$json_data['items'][0]['roundId'], 'round_id'); 
 		    // 	if($refund_check){ // If no refund for this round id
 		    // 		return 'Meron'; 
 		    // 	}

			$items_array = array();
		 	foreach ($json_data['items'] as $key):
		 
		 		// Trapping Data PlayerId is Null Use The Transaction Data and Pull PlayerId Based On The TransactionID
		 		if(isset($key['roundId']) && $key['roundId'] != ''):// if both playerid and roundid is missing
		 		 	$client_details = $this->_getClientDetails('player_id', $key['playerId']);
		 		 	$datatrans = $this->findTransactionRefund('RSG'.$key['roundId'], 'round_id');
		 		 	if(!$datatrans): // Transaction Not Found!
		 					$items_array[] = [
								 "info" => $key['info'], // IWininfo
								 "errorCode" => 7,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 1,
								 "items" => $items_array,
				   			);
				   			return $response;
		 			endif;
		 		else: // use originalTxid instead
		 			$datatrans = $this->findTransactionRefund($key['originalTxId'], 'transaction_id');
		 			if(!$datatrans): // Transaction Not Found!
		 					$items_array[] = [
								 "info" => $key['info'], // IWininfo
								 "errorCode" => 7,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 1,
								 "items" => $items_array,
				   			);
				   			return $response;
		 			endif;
		 			$jsonify = json_decode($datatrans->transaction_detail, true);
		 			$client_details = $this->_getClientDetails('player_id', $jsonify['items'][0]['playerId']);
		 		endif;
		 		// $client_details = $this->_getClientDetails('player_id', $key['playerId']);
		 		if(!$client_details){
		 				$items_array = array();
			 			foreach ($json_data['items'] as $key) :
							$items_array[] = [
								 "info" => $key['info'], // IWininfo
								 "errorCode" => 4,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 4,
								 "items" => $items_array,
				   			);
							Helper::saveLog('RSG REFUND GAME REQUEST', 14, file_get_contents("php://input"), $response);
				   			return $response;
			   		    endforeach;
		 		}
		 		// $client_details = $this->_getClientDetails('token', $key['token']);

		 		$payout_reason = $this->getOperationType($key['operationType']);
		 		$win_or_lost = 0;
		 		$method = 1;
		 		$income = null; // Sample
		 	    $token_id = $client_details->token_id;
		 	    if(isset($key['roundId'])){
		 	    	$round_id = 'RSGREFUND'.$key['roundId'];
		 	    }else{
		 	    	$round_id = 1;
		 	    }

		 	    if(isset($key['txId'])){
		 	    	$provider_trans_id = $key['txId'];
		 	    }else{
		 	    	$provider_trans_id = null;
		 	    }


		 		$client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
				if($key['refundRound'] == true):
					
   					    // Refund The Game
						$gg = json_decode($datatrans->transaction_detail);
						$amounts_array = array();
			 			foreach ($gg->items as $gg_tem) :
			 				if(isset($gg_tem->betAmount)):
			 					$item = $gg_tem->betAmount; // Bet return as credit
			 				else:
			 					$item = '-'.$gg_tem->winAmount; // Win return as debit
			 				endif;	
			 				array_push($amounts_array, $item);
				   		endforeach;

				   		foreach($amounts_array as $amnts):
				   			$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
			   				$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'], 3); // REFUND NEW ADDED
	 						if(!$check_win_exist):
	  				     	if(!$checkLog):

					   			if((int)$amnts > 0):
					   				$transactiontype = 'credit'; // Bet Amount should be returned as credit to player
					   			else:
					   				$transactiontype = 'debit'; // Win Amount should be returned as debit to player
					   			endif;	
					   			$amount = abs($amnts);
					   			$requesttosend = [
								  "access_token" => $client_details->client_access_token,
								  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
								  "type" => "fundtransferrequest",
								  "datetsent" => "",
								  "gamedetails" => [
								    "gameid" =>  "",
								    "gamename" => ""
								  ],
								  "fundtransferrequest" => [
										"playerinfo" => [
										"token" => $client_details->player_token,
									],
									"fundinfo" => [
									      "gamesessionid" => "",
									      "transactiontype" => $transactiontype,
									      "rollback" => "false",
									      "currencycode" => $client_details->currency, // This data was pulled from the client
									      "amount" => $amount
									]
								  ]
								];
								$round_id = isset($key['roundId']) ? $key['roundId'] : $gg_tem->roundId;
								$round_id = $gg_tem->roundId;
								$guzzle_response = $client->post($client_details->fund_transfer_url,
									['body' => json_encode($requesttosend)]
								);
								$client_response = json_decode($guzzle_response->getBody()->getContents());
								$balance_reply = $client_response->fundtransferresponse->balance;
								$game_details = Helper::findGameDetails('game_code', 14, $datatrans->game_id);
								$game_trans = Helper::saveGame_transaction($token_id, $gg_tem->gameId, $amount,  $amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
						 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
						   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	
						   		// $rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,3, $amount,$key['txId'], $gg_tem->roundId);

						   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,$json_data, 3, $amount, $key['txId'], $round_id);
						   		$items_array[] = [
				        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
									 "balance" => $balance_reply,
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 1,
									 "metadata" => "" // Optional but must be here!
				        	    ];
				        	else:
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 8, //already exist
									 "metadata" => "" // Optional but must be here!
							    ];   
							endif;      
				        	else:
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 8, //already exist
									 "metadata" => "" // Optional but must be here!
							    ];   
							endif;     
				   		endforeach;	
				   	
				else:
						$gg = json_decode($datatrans->transaction_detail);

			 			foreach ($gg->items as $gg_tem) :
			 				if(isset($gg_tem->betAmount)):
			 					$amount = $gg_tem->betAmount; // Bet return as credit
			 				else:
			 					$amount = '-'.$gg_tem->winAmount; // Win return as debit
			 				endif;	
				   		endforeach;
				   	
			   			if((int)$amount > 0):
			   				$transactiontype = 'credit'; // Bet Amount should be returned as credit to player
			   			else:
			   				$transactiontype = 'debit'; // Win Amount should be returned as debit to player
			   			endif;
			   			$amount = abs($amount);
			   			$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
 						if(!$check_win_exist):

 						$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'], 3); // REFUND NEW ADDED
	  					if(!$checkLog):

 							$requesttosend = [
							  "access_token" => $client_details->client_access_token,
							  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							  "type" => "fundtransferrequest",
							  "datetsent" => "",
							  "gamedetails" => [
							    "gameid" =>  "",
							    "gamename" => ""
							  ],
							  "fundtransferrequest" => [
									"playerinfo" => [
									"token" => $client_details->player_token,
								],
								"fundinfo" => [
								      "gamesessionid" => "",
								      "transactiontype" => $transactiontype,
								      "rollback" => "false",
								      "currencycode" => $client_details->currency, // This data was pulled from the client
								      "amount" => $amount
								]
							  ]
							];

							$guzzle_response = $client->post($client_details->fund_transfer_url,
								['body' => json_encode($requesttosend)]
							);

							$refund_round = $key['roundId'];
							$method = $transactiontype == 'credit' ? 2 : 1; 
							$client_response = json_decode($guzzle_response->getBody()->getContents());
							$balance_reply = $client_response->fundtransferresponse->balance;
							$game_details = Helper::findGameDetails('game_code', 14, $gg_tem->gameId);
							$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $amount,  $amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $refund_round);

					   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,$json_data, 3, $amount, $key['txId'] ,$refund_round);

					   		$items_array[] = [
			        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
								 "balance" => $balance_reply,
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 1,
								 "metadata" => "" // Optional but must be here!
			        	    ];
			        	else:
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 8, //already exist
								 "metadata" => "" // Optional but must be here!
						    ];   
						endif;     
			        	else:
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 8, //already exist
								 "metadata" => "" // Optional but must be here!
						    ];   
						endif; 
				endif;
			
			endforeach;
				$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
	   			);				
		endif;
			Helper::saveLog('RSG REFUND GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}

	/**
	 * UNDERCONSTRUCTION
	 */
	public function amend(){

		Helper::saveLog('RSG AMEND GAME REQUEST FIRST', 14, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 12 //Wrong Operator Id 
		];

		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):

			$items_array = array();
		 	foreach ($json_data['items'] as $key):

		 		$client_details = $this->_getClientDetails('player_id', $key['playerId']);
	 			if(!empty($client_details)):

		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
	 			if(!$check_win_exist):

	 			$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'],2);
	 			if(!$checkLog):
	 				
	 			if($key['currencyId'] == $client_details->default_currency): // Currency not match nb //


		 		// $datatrans = $this->findTransactionRefund($key['winTxId'], 'transaction_id');
	 		    $datatrans = $this->amendWin($key['roundId'], 1); // find if a bet for this win roundexist
	 		    if($datatrans):

	 			$datatrans = $this->amendWin($key['roundId'], 2); // find round ID wintransaction
	 			if($datatrans):

		 			$gametransaction_details = $this->findTransactionRefund('RSG'.$key['roundId'], 'round_id');

			 		$transaction_type =  $key['isCredit'] == true ? 'credit' : 'debit';
			 		$amount = $key['amendAmount'];
			
			 		$client = new Client([
	                    'headers' => [ 
	                        'Content-Type' => 'application/json',
	                        'Authorization' => 'Bearer '.$client_details->client_access_token
	                    ]
	                ]);
			 		// Send Amend Correction!		
					$requesttosend = [
					  "access_token" => $client_details->client_access_token,
					  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					  "type" => "fundtransferrequest",
					  "datetsent" => "",
					  "gamedetails" => [
					    "gameid" =>  $key['gameId'],
					    "gamename" => ""
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"token" => $client_details->player_token,
							// "playerId" => $key['playerId']
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => $transaction_type,
						      "rollback" => "true",
						      "currencycode" => $client_details->default_currency,
						      "amount" => $amount // Amount of ammend,
						]
					  ]
					];

					$guzzle_response = $client->post($client_details->fund_transfer_url,
						['body' => json_encode($requesttosend)]
					);

		 			$client_response = json_decode($guzzle_response->getBody()->getContents());
		 			// dd($client_response);
			 		//TEST GAME TRANSACTION LOGGING
			 		$payout_reason = 'Amend : '.$this->getOperationType($key['operationType']);
			 		$win_or_lost = $transaction_type == 'debit' ? 0 : 1;
			 		$method = $transaction_type == 'debit' ? 1 : 2;
			 		$income = null; // Sample
			 	    $token_id = $client_details->token_id;
			 	    if(isset($key['roundId'])){
			 	    	$round_id = 'RSG'.$key['roundId'];
			 	    }else{
			 	    	$round_id = 1;
			 	    }

			 	    if(isset($key['txId'])){
			 	    	$provider_trans_id = $key['txId'];
			 	    }else{
			 	    	$provider_trans_id = null;
			 	    }

			 	    $round_id = $key['roundId'];
			 	    // $game_details = Helper::findGameDetails('game_code', 14, $datatrans->game_id);
			 		// $game_trans = Helper::saveGame_transaction($token_id, $gameId, $amount, $amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
			 		
	 	  			if($key['isCredit'] == true): // CREADIT/ADD
						$pay_amount = $gametransaction_details->pay_amount + $amount;
	 	  				$income = $gametransaction_details->bet_amount - $pay_amount;
			 		else: // DEBIT/SUBTRACT
			 			$pay_amount = $gametransaction_details->pay_amount - $amount;
	 	  				$income = $gametransaction_details->bet_amount - $pay_amount;
			 		endif;


			 		if($pay_amount > $gametransaction_details->bet_amount):
			 			$win = 0; //lost
	 	  				$entry_id = 1; //lost
			 		else:
			 			$win = 1; //win
	 	  				$entry_id = 2; //win
			 		endif;

 	  				$updateTheBet = $this->updateBetToWin('RSG'.$key['roundId'], $pay_amount, $income, $win, $entry_id);
			 	
			 		$rsg_trans_ext = $this->createRSGTransactionExt($gametransaction_details->game_trans_id, $json_data, $requesttosend, $client_response, $client_response, $json_data, 3, $amount, $key['txId'],$round_id);

	        	    $items_array[] = [
	        	    	 "externalTxId" => $gametransaction_details->game_trans_id, // MW Game Transaction Id
						 "balance" => $client_response->fundtransferresponse->balance,
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 1,
						 "metadata" => "" // Optional but must be here!
	        	    ];

	        	else:
		 			$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 7, // Win Transaction not found
						 "metadata" => "" // Optional but must be here!
	        	    ];  
		 		endif;
	        	else:
		 			$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 7, // BEt Transaction not found
						 "metadata" => "" // Optional but must be here!
	        	    ];  
		 		endif;
		 		else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 16, // Currency code dont match!
						 "metadata" => "" // Optional but must be here!
	        	    ];   
	        	endif;  
			    else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;      	    
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;
	        	else:
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 4, //The playerId was not found
						 "metadata" => "" // Optional but must be here!
	        	    ];  
	        	endif;
		 	endforeach;

		 		$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
				);	
		endif;
		Helper::saveLog('RSG AMEND GAME REQUEST', 14, file_get_contents("php://input"), $response);
		return $response;
	}


	/**
	 * Pull out data from the Game exstension logs!
	 * 
	 */
	public static function checkRSGExtLog($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			$game = DB::table('game_transaction_ext')
				   ->where('provider_trans_id',$provider_transaction_id)
				   ->where('round_id',$round_id)
				   ->where('game_transaction_type',$type)
				   ->first();
		}
		else{
			$game = DB::table('game_transaction_ext')
				    ->where('provider_trans_id',$provider_transaction_id)
				    ->first();
		}
		return $game ? true :false;
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
	public  function createRSGTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){

		$provider_request_details = array();
		foreach($provider_request['items'] as $prd){
			$provider_request_details = $prd;
		}

		// game_transaction_type = 1=bet,2=win,3=refund	
		if($game_transaction_type == 1){
			// $amount = $provider_request_details['bet'];
			$amount = $amount;
		}elseif($game_transaction_type == 2){
			// $amount = $provider_request_details['winAmount'];
			$amount = $amount;
		}elseif($game_transaction_type == 3){
			$amount = $amount;
		}

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
	 * Find The Transactions For Refund, Providers Transaction ID
	 * 
	 */
    public  function findTransactionRefund($transaction_id, $type) {

    		$transaction_db = DB::table('game_transactions as gt')
					    	->select('gt.*', 'gte.transaction_detail')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
		 				   
		    if ($type == 'transaction_id') {
				$transaction_db->where([
			 		["gt.provider_trans_id", "=", $transaction_id],
			 	]);
			}
			if ($type == 'round_id') {
				$transaction_db->where([
			 		["gt.round_id", "=", $transaction_id],
			 	]);
			}
			if ($type == 'bet') { // TEST
				$transaction_db->where([
			 		["gt.round_id", "=", $transaction_id],
			 		["gt.payout_reason",'like', '%BET%'],
			 	]);
			}
			if ($type == 'refundbet') { // TEST
				$transaction_db->where([
			 		["gt.round_id", "=", $transaction_id],
			 	]);
			}
			$result= $transaction_db
	 			->latest('token_id')
	 			->first();

			if($result){
				return $result;
			}else{
				return false;
			}
	}

	/**
	 * Find The Transactions For Win/bet, Providers Transaction ID
	 * 
	 */
	public  function findGameTransaction($transaction_id) {
    		$transaction_db = DB::table('game_transactions as gt')
		 				   ->where('gt.provider_trans_id', $transaction_id)
		 				   // ->latest()
		 				   ->first();
		   	return $transaction_db ? $transaction_db : false;
	}

	/**
	 * Find win to amend
	 * @param $roundid = roundid, $transaction_type 1=bet, 2=win
	 * 
	 */
	public  function amendWin($roundid, $transaction_type) {
    		$transaction_db = DB::table('game_transactions as gt')
					    	->select('gt.token_id' ,'gte.*', 'gte.transaction_detail')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id")
						    ->where("gte.game_transaction_type" , $transaction_type) // Win Type
						    ->where("gte.round_id", $roundid)
						    ->first();
			return $transaction_db ? $transaction_db : false;
	}

	/**
	 * Find bet and update to win 
	 *
	 */
	public  function updateBetToWin($round_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => 'Bet updated to win'
	    		]);
		return ($update ? true : false);
	}

	/**
	 * Find The Transactions For Win/bet, Providers Transaction ID
	 */
	public  function findPlayerGameTransaction($round_id, $player_id) {
	    $player_game = DB::table('game_transactions as gts')
		    		->select('*')
		    		->join('player_session_tokens as pt','gts.token_id','=','pt.token_id')
                    ->join('players as pl','pt.player_id','=','pl.player_id')
                    ->where('pl.player_id', $player_id)
                    ->where('gts.round_id', $round_id)
                    ->first();
        // $json_data = json_encode($player_game);
	    return $player_game;
	}

	/**
	 * Find The Transactions For Refund, Providers Transaction ID
	 * @return  [<string>]
	 * 
	 */
    public  function getOperationType($operation_type) {

    	$operation_types = [
    		'1' => 'General Bet',
    		'2' => 'General Win',
    		'3' => 'Refund',
    		'4' => 'Bonus Bet',
    		'5' => 'Bonus Win',
    		'6' => 'Round Finish',
    		'7' => 'Insurance Bet',
    		'8' => 'Insurance Win',
    		'9' => 'Double Bet',
    		'10' => 'Double Win',
    		'11' => 'Split Bet',
    		'12' => 'Split Win',
    		'13' => 'Ante Bet',
    		'14' => 'Ante Win',
    		'15' => 'General Bet Behind',
    		'16' => 'General Win Behind',
    		'17' => 'Split Bet Behind',
    		'18' => 'Split Win Behind',
    		'19' => 'Double Bet Behind',
    		'20' => 'Double Win Behind',
    		'21' => 'Insurance Bet Behind',
    		'22' => 'Insurance Win Behind',
    		'23' => 'Call Bet',
    		'24' => 'Call Win',
    		'25' => 'Jackpot Bet',
    		'26' => 'Jackpot Win',
    		'27' => 'Tip',
    		'28' => 'Free Bet Win',
    		'29' => 'Free Spin Win',
    		'30' => 'Gift Bet',
    		'31' => 'Gift Win',
    		'32' => 'Deposit',
    		'33' => 'Withdraw',
    		'34' => 'Fee',
    		'35' => 'Win Tournament',
    		'36' => 'Cancel Fee',
    		'37' => 'Amend Credit',
    		'38' => 'Amend Debit',
    		'39' => 'Feature Trigger Bet',
    		'40' => 'Feature Trigger Win',
    	];
    	if(array_key_exists($operation_type, $operation_types)){
    		return $operation_types[$operation_type];
    	}else{
    		return 'Operation Type is unknown!!';
    	}

	}


	/**
	 * Helper method
	 * @return  [<Reversed Data>]
	 * 
	 */
	public function reverseDataBody($requesttosend){

		$reversed_data = $requesttosend;
	    $transaction_to_reverse = $reversed_data['fundtransferrequest']['fundinfo']['transactiontype'];
		$reversed_transaction_type =  $transaction_to_reverse == 'debit' ? 'credit' : 'debit';
		$reversed_data['fundtransferrequest']['fundinfo']['transactiontype'] = $reversed_transaction_type;
		$reversed_data['fundtransferrequest']['fundinfo']['rollback'] = 'true';

		return $reversed_data;
	}

	/**
	 * Client Player Details API Call
	 * @param $[data] [<array of data>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public function megaRollback($data_to_rollback, $items=[]){

		foreach($data_to_rollback as $rollback){
	    	try {
	    		$client = new Client([
				    'headers' => [ 
					    	'Content-Type' => 'application/json',
					    	'Authorization' => 'Bearer '.$rollback['header']
					    ]
				]);
				$datatosend = $rollback['body'];
				$guzzle_response = $client->post($rollback['url'],
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('RSG Rollback Succeed', 14, json_encode($datatosend), $client_response);
				Helper::saveLog('RSG Rollback Succeed', 14, json_encode($items), $client_response);
	    	} catch (\Exception $e) {
	    		Helper::saveLog('RSG rollback failed  response as item', 14, json_encode($datatosend), json_encode($items));
	    	}
		}

	}

	/**
	 * Client Player Details API Call
	 * @return [Object]
	 * @param $[player_token] [<players token>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public function playerDetailsCall($player_token, $refreshtoken=false){
		$client_details = DB::table("clients AS c")
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'c.default_currency', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
					 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
					 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
					 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
					 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
					 ->where("pst.player_token", "=", $player_token)
					 ->latest('token_id')
					 ->first();
		if($client_details){
			try{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				$datatosend = ["access_token" => $client_details->client_access_token,
					"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					"type" => "playerdetailsrequest",
					"clientid" => $client_details->client_id,
					"playerdetailsrequest" => [
						"token" => $player_token,
						// "playerId" => $client_details->client_player_id,
						"currencyId" => $client_details->currency,
						"gamelaunch" => false,
						"refreshtoken" => $refreshtoken
					]
				];
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
			 	return $client_response;
            }catch (\Exception $e){
               return false;
            }
		}else{
			return false;
		}
	}

	/**
	 * Client PInfo
	 * @return [Object]
	 * @param $[type] [<token, player_id, site_url, username>]
	 * @param $[value] [<value to be searched>]
	 * 
	 */
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
					 		// ["pst.status_id", "=", 1]
					 	]);
					}
					if ($type == 'player_id') {
						$query->where([
					 		["p.player_id", "=", $value],
					 		// ["pst.status_id", "=", 1]
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

	// public function findGameDetails($type, $provider_id, $identification) {
	// 	    $game_details = DB::table("games as g")
	// 			->leftJoin("providers as p","g.provider_id","=","p.provider_id");
				
	// 	    if ($type == 'game_code') {
	// 			$game_details->where([
	// 		 		["g.provider_id", "=", $provider_id],
	// 		 		["g.game_code",'=', $identification],
	// 		 	]);
	// 		}
	// 		$result= $game_details->first();
	//  		return $result;
	// }

}


// NOTES DONT DELETE!

// UPDATE ERROR CODES!
// Error code Error description
// 1 No errors were encountered
// 2 Session Not Found
// 3 Session Expired
// 4 Wrong Player Id
// 5 Player Is Blocked
// 6 Low Balance
// 7 Transaction Not Found
// 8 Transaction Already Exists
// 9 Provider Not Allowed For Partner
// 10 Provider's Action Not Found
// 11 Game Not Found
// 12 Wrong API Credentials
// 13 Invalid Method
// 14 Transaction Already Rolled Back
// 15 Wrong Operator Id
// 16 Wrong Currency Id
// 17 Request Parameter Missing
// 18 Invalid Data
// 19 Incorrect Operation Type
// 20 Transaction already won
// 999 General Error


// PREVIOUS AUTH CREDENTIALS
// private $apikey ="321dsfjo34j5olkdsf";
// private $access_token = "123iuysdhfb09875v9hb9pwe8f7yu439jvoiefjs";

// private $digitain_key = "rgstest";
// private $operator_id = '5FB4E74E';

// private $digitain_key = "rgstest";
// private $operator_id = 'D233911A';

// "operatorId":111,
// "timestamp":"202003092113371560",
// "signature":"ba328e6d2358f6d77804e3d342cdee06c2afeba96baada218794abfd3b0ac926",
// "token":"90dbbb443c9b4b3fbcfc59643206a123"
// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";