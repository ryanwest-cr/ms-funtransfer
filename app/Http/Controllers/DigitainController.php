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
 *  
 *	Api Documentation v3 -> v3.7.0-1
 *	Current State : v3 updating to v3.7.0-1    
 *  ## Authors Note : You cannot win if you dont bet! xD Bet comes first!
 *	## roundId is intentionally PREFIXED with RSG to separate from others roundid coz its crucial i select all of them!
 *	
 *	refund method additionals = requests: holdEarlyRefund
 *	win method additionals = requests:  returnBetsAmount, bonusTicketId
 *	bet method additionals = requests:  checkRefunded, bonusTicketId
 *	betwin method additionals = requests:  bonusTicketId,   ,response: playerId, roundId, currencyId
 *	
 */
class DigitainController extends Controller
{

	// private $apikey ="321dsfjo34j5olkdsf";
	// private $access_token = "123iuysdhfb09875v9hb9pwe8f7yu439jvoiefjs";

	// private $digitain_key = "rgstest";
    // private $operator_id = '5FB4E74E';

    // private $digitain_key = "rgstest";
    // private $operator_id = 'D233911A';


    private $digitain_key = "BetRNK3184223";
    private $operator_id = 'B9EC7C0A';

	public function authMethod($operatorId, $timestamp, $signature){
	  	// "operatorId":111,
		// "timestamp":"202003092113371560",
		// "signature":"ba328e6d2358f6d77804e3d342cdee06c2afeba96baada218794abfd3b0ac926",
		// "token":"90dbbb443c9b4b3fbcfc59643206a123"
		// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";
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


	// $timestampe = date('YmdHisms'); //sample output //202005041214380538

	public function createSignature($timestamp){
		// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";
	    // $operator_id = 'D233911A'; /* STATIC FOR NOW */
		// $digitain_key = "rgstest";
	    // $operator_id = '5FB4E74E';
	    $digitain_key = $this->digitain_key;
	    $operator_id = $this->operator_id;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    // $message = $digitain_key.$operator_id.$time_stamp;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
	    return $hmac;
	}

	// TEST
	public function getTimestamp($daterequest){
		$date1 = str_replace("-", "", $daterequest);
		$date2 = str_replace(":", "", $date1);
		$date3 = str_replace(" ", "", $date2);
		return $date3;
	}

	/**
     * DEPRECATED! Centralized!
     * 
     * # Request , token, username, email, site_url, gamecode
     * 
	 */
	public function createGameSession(){
		Helper::saveLog('Game Session  RSG', 14, file_get_contents("php://input"), 'AUTH');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$check_client = $this->checkClientPlayer($json_data['site_url'], 
												 $json_data['playerdetailsrequest']['username'], 
												 $json_data['playerdetailsrequest']['token']);
		if($check_client['httpstatus'] != 200){
				return $check_client;
		}
			$client_details = $this->_getClientDetails('token', $json_data['playerdetailsrequest']['token']);
			 $response = [
			 	"errorcode" =>  "CLIENT_NOT_FOUND",
				"errormessage" => "Client not found",
				"httpstatus" => "404"
			 ];
			 if ($client_details) { 
			 // 	$subscription = new GameSubscription();
				// $client_game_subscription = $subscription->check($client_details->client_id, 11, $json_data['gamecode']);

			 // 	if(!$client_game_subscription) {
				// 	$response = [
				// 			"errorcode" =>  "GAME_NOT_FOUND",
				// 			"errormessage" => "Game not found",
				// 			"httpstatus" => "404"
				// 		];
				// }
				// else{
					$response = array(
                                "url" => 'https://partnerapirgs.betadigitain.com/GamesLaunch/Launch?gameid='.$json_data['gamecode'].'&playMode=real&token='.$json_data['playerdetailsrequest']['token'].'&deviceType=1&lang=EN&operatorId='.$this->operator_id.'&mainDomain='.$json_data['site_url'].'',
                                "game_launch" => true
                            );
				// }
			 }
			 Helper::saveLog('Client Game Session RSG', 14, file_get_contents("php://input"), $response);
	         return $response;
	}

	/**
	 *
	 *	Player Detail Request
	 *
	 */
    public function authenticate(Request $request)
    {	
    	// Helper::saveLog('Auth  RSG REQUESTED', 14, 'LOGS', 'AUTH');
    	// Helper::saveLog('Auth  RSG', 14, file_get_contents("php://input"), 'AUTH');
		$json_data = json_decode(file_get_contents("php://input"), true);
		// Helper::saveLog('Authentication RSG', 14, file_get_contents("php://input"), 'refreshtoken');
		$response = [
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpstatus" => "404",
						"errorCode" =>  23,
					];
		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {
		$player_token = $json_data["token"];
		$client_details = DB::table("clients AS c")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("pst.player_token", $player_token)
						 ->first();
			if ($client_details) {
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
								"datesent" => "",
								"gameid" => "",
								"clientid" => $client_details->client_id,
								"playerdetailsrequest" => [
									"token" => $json_data["token"],
									"gamelaunch" => true
								]
							]
				    )]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				// $time_formatted = $this->getTimestamp($client_response->playerdetailsresponse->daterequest);
				// dd($client_response->playerdetailsresponse->status->code);
				if(isset($client_response->playerdetailsresponse->status->code) &&
					     $client_response->playerdetailsresponse->status->code == "200"){
					$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"errorCode" => 1,
						// "playerId" => $client_response->playerdetailsresponse->accountid,
						"playerId" => $client_details->player_id, // Player ID Here is Player ID in The MW DB, not the client!
						"userName" => $client_response->playerdetailsresponse->accountname,
						"currencyId" => $client_response->playerdetailsresponse->currencycode,
						"balance" => $client_response->playerdetailsresponse->balance,
						// "birthDate" => $client_response->playerdetailsresponse->birthday,
						// "birthDate" => '1991-03-09 00:00:00.000',
						"birthDate" => '',
						"firstName" => $client_response->playerdetailsresponse->firstname,
						"lastName" => $client_response->playerdetailsresponse->lastname,
						// "gender" => $client_response->playerdetailsresponse->gender,
						"gender" => '',
						"email" => $client_response->playerdetailsresponse->email,
						"isReal" => true
					];
				}
				Helper::saveLog('Authentication RSG', 2, file_get_contents("php://input"), $response);
				// echo json_encode($response);
				return json_encode($response);
			}
		
		}else{
			Helper::saveLog('Authentication RSG', 2, file_get_contents("php://input"), $response);
			return $response;
		}
	}

	/**
	 * Get the player balance
	 */
	public function getBalance()
	{
		Helper::saveLog('BALANCE RSG REQUESTED', 14, 'LOGS', 'LOGS');
		Helper::saveLog('GET BALANCE RSG', 14, file_get_contents("php://input"), 'FA');
		$json_data = json_decode(file_get_contents("php://input"), true);
		// Helper::saveLog('Player Balance RSG', 2, file_get_contents("php://input"), '1ST REQUEST');
		$response = [
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpstatus" => "404",
						"errorCode" => 12
					];
		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {
		$player_token = $json_data["token"];
		$client_details = DB::table("clients AS c")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("pst.player_token", $player_token)
						 ->first();

		if ($client_details) {
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
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $json_data["token"],
								"playerId" => $json_data["playerId"],
								"currencyId" => $json_data["currencyId"],
								"gamelaunch" => true
							]
						]
			    )]
			);
			$client_response = json_decode($guzzle_response->getBody()->getContents());
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 1,
				"balance" => $client_response->playerdetailsresponse->balance,
				"email" => $client_response->playerdetailsresponse->email,
				"updatedAt" => date('YmdHis.ms')
			];

		}
			Helper::saveLog('PLAYER BALANCE RSG', 2, file_get_contents("php://input"), $response);
			return json_encode($response);
		}else{
			Helper::saveLog('PLAYER BALANCE RSG', 2, file_get_contents("php://input"), $response);
			return json_encode($response);
		}
	}



	public function refreshtoken(){
		Helper::saveLog('RTOKEN RSG REQUESTED', 14, 'LOGS', 'LOGS');
		Helper::saveLog('Auth Refresh Token RSG', 14, file_get_contents("php://input"), 'FIRST');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"errorcode" =>  "INVALID_TOKEN",
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {
			$client_details = $this->_getClientDetails('token', $json_data['token']);
			if(!$client_details){
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 4, 
		   			);
		   			Helper::saveLog('RGS REFREST TOKEN GAME REQUEST', 14, file_get_contents("php://input"), $response);
		   			return $response;
	 		}
			if($client_details){
			 	// IF TRUE REQUEST ADD NEW TOKEN
			 	if($json_data['changeToken']){
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
									"clientid" => $client_details->client_id,
									"playerdetailsrequest" => [
										"token" => $json_data["token"],
										"gamelaunch" => true,
										"refreshtoken" => true
									]
								]
					    )]
					);
					$client_response = json_decode($guzzle_response->getBody()->getContents());
				 	DB::table('player_session_tokens')->insert(
	                            array('player_id' => $client_details->player_id, 
	                            	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
	                            	  'status_id' => '1')
	                );
					$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"token" => $client_response->playerdetailsresponse->refreshtoken,
						"errorCode" => 1
					];
			 	}else{
			 		$response = [
						"timestamp" => date('YmdHisms'),
						"signature" => $this->createSignature(date('YmdHisms')),
						"token" => $json_data['token'],
						"errorCode" => 1
					];
			 	}
			 }else{
			 	$response = [
					"errorCode" => 4,
					"errormessage" => 'Player Token Not Found!',
				];
			 }
		}
		Helper::saveLog('Auth Refresh Token RSG', 14, file_get_contents("php://input"), $response);
		return $response;
	}
	
	/**
	 *	
	 * NOTE
	 * allOrNone - When True, if any of the items fail, the Partner should reject all items NO LOGIC YET!
	 * checkRefunded - no logic yet
	 * ignoreExpiry - no logic yet, expiry should be handle in the refreshToken call
	 * changeBalance - no yet implemented always true (RSG SIDE)
	 * 
	 *	
	 */
	public function bet(Request $request){
		// Helper::saveLog('BET RSG REQUESTED', 14, 'LOGS', 'LOGS');
		// Helper::saveLog('RSG BET GAME REQUEST FIRST', 14, file_get_contents("php://input"), '1');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];

		// if(true) {
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
			$items_array = array();
			if($json_data['allOrNone'] == true){
				foreach ($json_data['items'] as $key) {
					$items_array[] = [
						 "info" => $key['info'], // IWininfo
						 "errorCode" => 7,
						 "metadata" => "" // Optional but must be here!
	        	    ];
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "Items" => $items_array,
		   			);
		   		}
				Helper::saveLog('RSG BET GAME REQUEST', 14, file_get_contents("php://input"), $response);
	   			return $response;
			}

			$items_array = array();
		 	foreach ($json_data['items'] as $key):
		 		$client_details = $this->_getClientDetails('token', $key['token']);
		 		// dd($client_details);
		 		if(!$client_details){
		 				$items_array = array();
			 			foreach ($json_data['items'] as $key) {
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 4,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 4, 
								 "Items" => $items_array,
				   			);
			   			}
			   			Helper::saveLog('RSG BET GAME REQUEST', 14, file_get_contents("php://input"), $response);
			   			return $response;
		 		}

		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
		 		if(!$check_win_exist):
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
							// "playerId" => $key['playerId']
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "currencycode" => $client_details->currency,
						      "amount" => $key['betAmount']
						]
					  ]
					];

					$guzzle_response = $client->post($client_details->fund_transfer_url,
						['body' => json_encode($requesttosend)]
					);

			 		$client_response = json_decode($guzzle_response->getBody()->getContents());

			 		$payout_reason = 'Bet : '.$this->getOperationType($key['operationType']);
			 		$win_or_lost = 0;
			 		$method = 1;
			 		// $income = null; // Sample
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
			 		// $game_trans = Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
			 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
			   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	

			   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response, $json_data, 1, $key['betAmount'], $key['txId'] ,$key['roundId']);

	        	    $items_array[] = [
	        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
						 "balance" => $client_response->fundtransferresponse->balance,
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
			endforeach;
				$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "Items" => $items_array,
	   			);				
		endif;
			Helper::saveLog('RSG BET GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}



	/**
	 *	
	 * NOTE
	 * token - dont have token in win call!
	 *	
	 */
	public function win(Request $request){
		// return 'hold on xD';
		// Helper::saveLog('WIN RSG REQUESTED', 14, 'LOGS', 'LOGS');
		// Helper::saveLog('RSG WIN GAME REQUEST FIRST', 14, file_get_contents("php://input"), '1');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):		
			if($json_data['allOrNone'] == true){
				$items_array = array();
				foreach ($json_data['items'] as $key) {
					$items_array[] = [
						 "info" => $key['info'], // IWininfo
						 "errorCode" => 7,
						 "metadata" => "" // Optional but must be here!
	        	    ];
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "Items" => $items_array,
		   			);
		   		}
				Helper::saveLog('RSG WIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
	   			return $response;
			}


			// OUTSIDE FILTER
			// $item_filters = array();
			// foreach ($json_data['items'] as $key):
			// 		$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'],2);
			// 		dd($checkLog);
			//  		if($checkLog):
			//  			// dd('hahah double entry ka!');
			//  			// foreach ($json_data['items'] as $key):
			// 	 			$item_filters[] = [
			// 					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
			// 					 "errorCode" => 8, //already exist
			// 					 "metadata" => "123123" // Optional but must be here!
			//         	    ];
			//         	// endforeach;
		 //    //     	    $response = array(
			// 			// 		 "timestamp" => date('YmdHisms'),
			// 			// 	     "signature" => $this->createSignature(date('YmdHisms')),
			// 			// 		 "errorCode" => 1,
			// 			// 		 "Items" => $item_filter,
			// 	  //  		);	
			// 	  //  		Helper::saveLog('RSG WIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
			// 			// return $response;
			//  		endif;
			// endforeach;

			// return $item_filters;
			$items_array = array();
			foreach ($json_data['items'] as $key):
				$client_details = $this->_getClientDetails('player_id', $key['playerId']);
		 		if(!$client_details){
		 				$items_array = array();
			 			foreach ($json_data['items'] as $key) {
							 $items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 4,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 4, 
								 "Items" => $items_array,
				   			);
			   			}
			   			Helper::saveLog('RSG WIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
			   			return $response;
		 		}

		 		// $check_win_exist = $this->findGameTransaction('RSG'.$key['txId'].'WIN'); // if exist bypass
		 		$check_win_exist = $this->findGameTransaction($key['txId']); // if transaction id exist bypass it
	 			if(!$check_win_exist):
	 		
	 			$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'],2);
	 			if(!$checkLog):

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
							      "currencycode" => $client_details->currency, // This data was pulled from the client
							      "amount" => $key['winAmount']
							]
						  ]
						];

				 		$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

				 		$client_response = json_decode($guzzle_response->getBody()->getContents());
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
	        	    		$bet_transaction = $this->findGameTransaction($key['betTxId']);
	        	    		$bet_transaction = $bet_transaction->bet_amount;
	        	    	}else{
	        	    		$bet_transaction = $this->findPlayerGameTransaction('RSG'.$key['roundId'], $key['playerId']);
	        	    		$bet_transaction = $bet_transaction->bet_amount;
	        	    	}
				 			$income = $bet_transaction - $key['winAmount']; // Sample	
				 	  		$game_details = Helper::findGameDetails('game_code', 14, $key['gameId']);				
				 	  		$game_trans = Helper::saveGame_transaction($token_id, $game_details->game_id, $bet_transaction,  $key['winAmount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
				 			
				 	  	// 	$game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
				   		// DB::table('game_transaction_ext')->insert($game_trans_ext);	
				 			$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,$json_data, 2, $key['winAmount'], $key['txId'] ,$key['roundId']);

			        	    $items_array[] = [
			        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
								 "balance" => $client_response->fundtransferresponse->balance,
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 1,
								 "metadata" => "", // Optional but must be here!
			        	    ];
			        	    if(isset($key['returnBetsAmount']) && $key['returnBetsAmount'] == true): // SCENARIO
			        	    	if(isset($key['betTxId'])){
			        	    		$datatrans = $this->findTransactionRefund($key['betTxId'], 'transaction_id');
			        	    		// dd('betTxId');
			        	    	}else{
			        	    		// $datatrans = $this->findTransactionRefund('RSG'.$key['roundId'], 'bet');
			        	    		$datatrans = $this->findTransactionRefund('RSG'.$key['roundId'], 'round_id');
			        	    	}
			        	    	$gg = json_decode($datatrans->transaction_detail);
						 		$total_bets = array();
						 		foreach ($gg->Items as $gg_tem) {
									$total_bets[] = $gg_tem->betAmount;
						 		}
				        	    $items_array[0]['betsAmount'] = array_sum($total_bets);
			        	    endif;
			    else:
	        		// dd('hahah double entry ka!');
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;      	    
	        	else:
	        		// dd('hahah double entry ka!');
	        		$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 8, //already exist
						 "metadata" => "" // Optional but must be here!
	        	    ]; 
	        	endif;    
			endforeach;
        	    $response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "Items" => $items_array,
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

		Helper::saveLog('BETWIN RSG REQUESTED', 14, 'LOGS', 'LOGS');
		Helper::saveLog('RSG BETWIN GAME REQUEST FIRST', 14, file_get_contents("php://input"), '1');
		$json_data = json_decode(file_get_contents("php://input"), true);
		// Helper::saveLog('Bet Request RSG', 14, file_get_contents("php://input"), 'bet');
		// return $json_data;
		// return 'under construction XD';
		$response = [
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];
		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {

			if($json_data['allOrNone'] == true){
				$items_array = array();
				foreach ($json_data['items'] as $key) {
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 7,
						 "metadata" => "" // Optional but must be here!
	        	    ];
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "Items" => $items_array,
		   			);
					
		   		}
		   		Helper::saveLog('RSG BETWIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
		   		return $response;
			}

			$items_array = array();
		 	foreach ($json_data['items'] as $key) {
		 		$client_details = $this->_getClientDetails('token', $key['token']);
		 		if(!$client_details){
		 				$items_array = array();
			 			foreach ($json_data['items'] as $key) {
							$items_array[] = [
								 "betInfo" => $key['betInfo'], // Betinfo
								 "winInfo" => $key['winInfo'], // IWininfo
								 "errorCode" => 4,
								 "metadata" => "" // Optional but must be here!
			        	    ];
							$response = array(
								 "timestamp" => date('YmdHisms'),
							     "signature" => $this->createSignature(date('YmdHisms')),
								 "errorCode" => 4, 
								 "items" => $items_array,
				   			);
			   			}
			   			Helper::saveLog('RSG BETWIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
			   			return $response;
		 		}


		 		// $client_details = $this->_getClientDetails('token', $key['token']);
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

		 		// Helper::savePLayerGameRound($key['gameId'],$client_details->player_token);
		 		// $game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
		 		// $json_data = array(
     //                "transid" => $key['txId'],
     //                "amount" => $key['betAmount'],
     //                "roundid" => $key['gameId']
     //            );
		 		// $game_trans = Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);

		 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
		   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	

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
					      "currencycode" => $client_details->currency, // This data was pulled from the client
					      "amount" => $key['winAmount']
					]
				  ]
				];
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);

		 		$client_response_ii = json_decode($guzzle_response->getBody()->getContents());
		 		// TEST GAME TRANSACTION LOGGING
		 		$payout_reason = 'Win : '.$this->getOperationType($key['winOperationType']);
		 		$win_or_lost = 1;
		 		$method = 2;
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

		 		$game_trans = Helper::saveGame_transaction($token_id, $key['gameId'], $key['winAmount'],  $key['winAmount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
		 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
		   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	
		   		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response,$json_data, 2, $key['winAmount'], $key['txId'] ,$key['roundId']);
        	    $items_array[] = [
        	    	 "externalTxId" => $game_trans, // MW Game Transaction Only Save The Last Game Transaction Which is the credit!
					 "balance" => $client_response_ii->fundtransferresponse->balance,
					 "betInfo" => $key['betInfo'], // Betinfo
					 "winInfo" => $key['winInfo'], // IWininfo
					 "errorCode" => 1,
					 "metadata" => "" // Optional but must be here!
        	    ];
			}
				$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "Items" => $items_array,
	   			);				
		}
			Helper::saveLog('RSG BETWIN GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}


	/**
	 * Refund Find Logs According to gameround, or TransactionID and refund whether it  a bet or win
	 *
	 * refundOriginalBet (No proper explanation on the doc!)	
	 * originalTxtId = either its winTxd or betTxd	
	 * refundround is true = always roundid	
	 * if roundid is missing always originalTxt, same if originaltxtid use roundId
	 *
	 */
	public function refund(Request $request){
		// dd(1);
		// Helper::saveLog('REFUND RSG REQUESTED', 14, 'LOGS', 'LOGS');
		// Helper::saveLog('RSG REFUND GAME REQUEST FIRST', 14, file_get_contents("php://input"), '1');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];

		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])):
			// We dont accept allOrNone!
			// dd(2);
			if($json_data['allOrNone'] == true){
				$items_array = array();
				foreach ($json_data['items'] as $key) :
					$items_array[] = [
						 "info" => $key['info'], // IWininfo
						 "errorCode" => 17,
						 "metadata" => "" // Optional but must be here!
	        	    ];
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "Items" => $items_array,
		   			);
					Helper::saveLog('RSG REFUND GAME REQUEST', 14, file_get_contents("php://input"), $response);
		   			return $response;
		   		endforeach;
			}
	 		// CHECK REFUND IF ALREADY ARRIVED!
	 		// $refund_check = $this->findTransactionRefund('RSGREFUND'.$json_data['items'][0]['roundId'], 'round_id'); 
 		    // 	if($refund_check){ // If no refund for this round id
 		    // 		return 'Meron'; 
 		    // 	}
			$items_array = array();
		 	foreach ($json_data['items'] as $key):
		 		// We dont want early refund xD
		 		// if($key['holdEarlyRefund'] == false):
 				// 		$items_array[] = [
					// 		 "info" => $key['info'], // IWininfo
					// 		 "errorCode" => 7,
					// 		 "metadata" => "" // Optional but must be here!
		   //      	    ];
					// 	$response = array(
					// 		 "timestamp" => date('YmdHisms'),
					// 	     "signature" => $this->createSignature(date('YmdHisms')),
					// 		 "errorCode" => 1,
					// 		 "Items" => $items_array,
			  //  			);
					// 	Helper::saveLog('RSG REFUND GAME REQUEST DENIED', 14, file_get_contents("php://input"), $response);
			  //  			return $response;
		 		// endif;
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
								 "Items" => $items_array,
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
								 "Items" => $items_array,
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
								 "Items" => $items_array,
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
	 						if(!$check_win_exist):

	 						$checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'], 3); // REFUND NEW ADDED
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
					 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
					   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	
					   		// $rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response, 3, $amount, $key['txId'], (int)$key['roundId']);

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
					 "Items" => $items_array,
	   			);				
		endif;
			Helper::saveLog('RSG REFUND GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}


	public function amend(){
		Helper::saveLog('AMEND RSG REQUESTED', 14, 'LOGS', 'LOGS');
		Helper::saveLog('RSG AMEND GAME REQUEST FIRST', 14, file_get_contents("php://input"), '1');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$response = [
			"errormessage" => "The provided token could not be verified/Token already authenticated",
			"httpstatus" => "404",
			"errorCode" => 12,
		];


		if($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {
			if($json_data['allOrNone'] == true){
				$items_array = array();
				foreach ($json_data['items'] as $key) {
					$items_array[] = [
						 "info" => $key['info'], // IWininfo
						 "errorCode" => 7,
						 "metadata" => "" // Optional but must be here!
	        	    ];
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "items" => $items_array,	
		   			);
					Helper::saveLog('RSG AMEND GAME REQUEST', 14, file_get_contents("php://input"), $response);
		   			return $response;
		   		}
			}

			$items_array = array();
		 	foreach ($json_data['items'] as $key) {
		 		// dd(1);
		 		$client_details = $this->_getClientDetails('player_id', $key['playerId']);
		 		$datatrans = $this->findTransactionRefund($key['winTxId'], 'transaction_id');
	 			$jsonify = json_decode($datatrans->transaction_detail, true);
	 			if(isset($jsonify['items'][0]['winAmount'])){
	 				// return 'its a win';
	 				$transactiontype = 'debit';
	 				$amount = $jsonify['items'][0]['winAmount'];
	 				$gameId = $jsonify['items'][0]['gameId'];
	 			}else{
	 				// return 'its a bet';
	 				$transactiontype = 'credit';
	 				$amount = $jsonify['items'][0]['betAmount'];
	 				$gameId = $jsonify['items'][0]['gameId'];
	 			}
	 			// return 'ditokalang!';
		 		if(!$client_details){
		 				$items_array = array();
			 			foreach ($json_data['items'] as $key) {
						$items_array[] = [
							 "info" => $key['info'], // IWininfo
							 "errorCode" => 7,
							 "metadata" => "" // Optional but must be here!
		        	    ];
						$response = array(
							 "timestamp" => date('YmdHisms'),
						     "signature" => $this->createSignature(date('YmdHisms')),
							 "errorCode" => 4,
							 "items" => $items_array,
			   			);
						Helper::saveLog('RSG AMEND GAME REQUEST', 14, file_get_contents("php://input"), $response);
			   			return $response;
			   		}
		 		}
		 		$transaction_type =  $key['isCredit'] == true ? 'credit' : 'debit';
		 		$amount = $key['amendAmount'];
		 		// $client_details = $this->_getClientDetails('token', $key['token']);
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
					      "currencycode" => $client_details->currency,
					      "amount" => $amount // Amount of ammend,
					]
				  ]
				];
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);

	 			$client_response = json_decode($guzzle_response->getBody()->getContents());
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
		 	    $game_details = Helper::findGameDetails('game_code', 14, $datatrans->game_id);
		 		$game_trans = Helper::saveGame_transaction($token_id, $gameId, $amount, $amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
		 		// $game_trans_ext = ["game_trans_id" => $game_trans, "transaction_detail" => file_get_contents("php://input")];
		   // 		DB::table('game_transaction_ext')->insert($game_trans_ext);	
		 		$rsg_trans_ext = $this->createRSGTransactionExt($game_trans, $json_data, $requesttosend, $client_response, $client_response, $json_data, 3, $amount, $key['txId'],$round_id);
        	    $items_array[] = [
        	    	 "externalTxId" => $game_trans, // MW Game Transaction Id
					 "balance" => $client_response->fundtransferresponse->balance,
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 1,
					 "metadata" => "" // Optional but must be here!
        	    ];
			}
				$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
	   			);				
		}
			Helper::saveLog('RSG AMEND GAME REQUEST', 14, file_get_contents("php://input"), $response);
			return $response;
	}


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

	public  function createRSGTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){

		$provider_request_details = array();
		// $provider_request['items'][0]['winAmount']
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
	 */
    public  function findTransactionRefund($transaction_id, $type) {

    		$transaction_db = DB::table('game_transactions as gt')
					    	->select('gt.*', 'gte.transaction_detail')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
		 				   // ->where('gt.provider_trans_id', $transaction_id)
		 				   // ->latest()
		 				   // ->first();
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
	 */
	public  function findGameTransaction($transaction_id) {
    		$transaction_db = DB::table('game_transactions as gt')
		 				   ->where('gt.provider_trans_id', $transaction_id)
		 				   // ->latest()
		 				   ->first();
		   	return $transaction_db ? $transaction_db : false;
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
	 */
    public  function getOperationType($operation_type) {

    		switch ($operation_type) {
				case 1:
					$message = 'General Bet';
					break;
				case 2:
					$message = 'General Win';
					break;
				case 3:
					$message = 'Refund';
					break;
				case 4:
					$message = 'Bonus Bet';
					break;
				case 5:
					$message = 'Bonus Win ';
					break;
				case 6:
					$message = 'Round Finish';
					break;
				case 7:
					$message = 'Insurance Bet';
					break;
				case 8:
					$message = 'Insurance Win';
					break;
				case 9:
					$message = 'Double Bet';
					break;
				case 10:
					$message = 'Double Win';
					break;
				case 11:
					$message = 'Split Bet';
					break;
				case 12:
					$message = 'Split Win';
					break;
				case 13:
					$message = 'Ante Bet ';
					break;
				case 14:
					$message = 'Ante Win';
					break;
				case 15:
					$message = 'General Bet Behind';
					break;
				case 16:
					$message = 'General Win Behind';
					break;
				case 17:
					$message = ' Split BetBehind';
					break;
				case 18:
					$message = 'Split Win Behind';
					break;
				case 19:
					$message = 'Double Bet Behind';
					break;
				case 20:
					$message = 'Double Win Behind';
					break;
				case 21:
					$message = 'Insurance Bet Behind';
					break;
				case 22:
					$message = 'Insurance Win Behind';
					break;	
				case 23:
					$message = 'Call Bet';
					break;	
				case 24:
					$message = 'Call Win';
					break;	
				case 25:
					$message = 'Jackpot Bet';
					break;	
				case 26:
					$message = 'Jackpot Win';
					break;	
				case 27:
					$message = 'Tip';
					break;	
				case 28:
					$message = 'Free Bet Win';
					break;	
				case 29:
					$message = 'Free Spin Win';
					break;	
				case 30:
					$message = 'Gift Bet';
					break;	
				case 31:
					$message = 'Gift Win';
					break;	
				case 32:
					$message = 'Deposit';
					break;	
				case 33:
					$message = 'Withdraw';
					break;	
				case 34:
					$message = 'Fee';
					break;	
				case 35:
					$message = 'Win Tournament';
					break;	
				case 36:
					$message = 'Cancel Fee';
					break;	
				case 37:
					$message = 'Amend Credit';
					break;	
				case 38:
					$message = 'Amend Debit';
					break;																																			
				default:		  
			}	
				return $message;

	}



    /*
     * DEPRECATED CENTRALIZED!
	 * Check Player Using Token if its already register in the MW database if not register it!
	 *
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
												"datesent" => "",
												"gameid" => "",
												"clientid" => $client_details->client_id,
												"playerdetailsrequest" => [
													"token" => $token,
													"gamelaunch" => true
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
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
}
