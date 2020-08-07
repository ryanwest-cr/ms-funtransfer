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
use App\Helpers\PlayerHelper;
use App\Helpers\TokenHelper;

use App\Support\RouteParam;

use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DB;

class SolidGamingController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function show(Request $request) { }

	public function authPlayer(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'token')) {
				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "INVALID_TOKEN",
							"errormessage" => "The provided token could not be verified/Token already authenticated",
						];
			
			$client_details = $this->_getClientDetails('token', $json_data['token']);
			
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
								"datesent" => Helper::datesent(),
								"gameid" => "",
								"clientid" => $client_details->client_id,
								"playerdetailsrequest" => [
									"client_player_id" => $client_details->client_player_id,
									"token" => $json_data["token"],
									"gamelaunch" => true
								]]
				    )]
				);
				
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				
				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					// save player details if not exist
					$player_id = PlayerHelper::saveIfNotExist($client_details, $client_response);

					// save token to system if not exist
					TokenHelper::saveIfNotExist($player_id, $json_data["token"]);

					$http_status = 200;
					$response = [
						"status" => "OK",
						"brand" => 'BETRNKMW',
						"playerid" => "$player_id",
						"currency" => $client_details->currency,
						"balance" => $client_response->playerdetailsresponse->balance,
						"testaccount" => false,
						"wallettoken" => "",
						"country" => "",
						"affiliatecode" => "",
						"displayname" => $client_response->playerdetailsresponse->accountname,
					];
				}
				else
				{
					// change token status to expired
					// TokenHelper::changeStatus($player_id, 'expired');
				}
			}
		}

		Helper::saveLog('authentication', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function getPlayerDetails(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		if(!CallParameters::check_keys($json_data, 'playerid')) {
				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "The provided playerid don’t exist.",
						];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details) {
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode(
				        	[
				        		"access_token" => $client_details->client_access_token,
								"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
								"type" => "playerdetailsrequest",
								"datesent" => "",
								"gameid" => "",
								"clientid" => $client_details->client_id,
								"playerdetailsrequest" => [
									"client_player_id" => $client_details->client_player_id,
									"token" => $client_details->player_token,
									"gamelaunch" => "true"
								]]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());		
				
				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					$http_status = 200;
					$response = [
						"status" => "OK",
						"brand" => 'BETRNKMW',
						"currency" => $client_details->currency,
						"testaccount" => false,
						"country" => "",
						"affiliatecode" => "",
						"displayname" => $client_response->playerdetailsresponse->accountname,
					];
				}
			}
		}

		Helper::saveLog('playerdetails', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'playerid', 'gamecode', 'platform')) {
				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
						];

			// Find the player and client details
			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/
			
			if ($client_details) {

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

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
					        	[
					        		"access_token" => $client_details->client_access_token,
									"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									"type" => "playerdetailsrequest",
									"datesent" => "",
									"gameid" => "",
									"clientid" => $client_details->client_id,
									"playerdetailsrequest" => [
										"client_player_id" => $client_details->client_player_id,
										"token" => $client_details->player_token,
										"gamelaunch" => "false"
									]]
					    )]
					);

					$client_response = json_decode($guzzle_response->getBody()->getContents());
					
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

						$http_status = 200;
						$response = [
							"status" => "OK",
							"currency" => $client_details->currency,
							"balance" => $client_response->playerdetailsresponse->balance,
						];
					}
				}
			}
		}

		Helper::saveLog('balance', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function debitProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'playerid', 'roundid', 'gamecode', 'platform', 'transid', 'currency', 'amount', 'reason', 'roundended')) {
				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
						];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details) {
				GameRound::create($json_data['roundid'], $client_details->token_id);

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
							"httpstatus" => "404"
						];
				}
				else
				{
					if(!GameRound::check($json_data['roundid'])) {
						$http_status = 400;
						$response = [
							"errorcode" =>  "ROUND_ENDED",
							"errormessage" => "Game round have already been closed",
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
						
						$body = json_encode(
						        	[
									  "access_token" => $client_details->client_access_token,
									  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									  "type" => "fundtransferrequest",
									  "datetsent" => "",
									  "gamedetails" => [
									    "gameid" => "",
									    "gamename" => ""
									  ],
									  "fundtransferrequest" => [
											"playerinfo" => [
												"client_player_id" => $client_details->client_player_id,
												"token" => $client_details->player_token
										],
										"fundinfo" => [
										      "gamesessionid" => "",
										      "transactiontype" => "debit",
										      "transferid" => "",
										      "rollback" => "false",
										      "currencycode" => $client_details->currency,
										      "amount" => $json_data["amount"]
										]
									  ]
									]
						    );

						$guzzle_response = $client->post($client_details->fund_transfer_url,
						    ['body' => $body]
						);

						$client_response = json_decode($guzzle_response->getBody()->getContents());

						/*var_dump($client_response); die();*/

						if(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "402") {
							$http_status = 402;
							$response = [
								"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
								"errormessage" => "Not sufficient funds",
							];
						}
						else
						{
							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "200") {

								if(array_key_exists("roundended", $json_data)) {
									if ($json_data["roundended"] == "true") {
										GameRound::end($json_data['roundid']);
									}
								}

								$json_data['income'] = $json_data['amount'];

								$game_details = Game::find($json_data["gamecode"]);
								GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

								$http_status = 200;
								$response = [
									"status" => "OK",
									"currency" => $client_details->currency,
									"balance" => $client_response->fundtransferresponse->balance,
								];
							}
						}
					}
				}
			}
		}
		
		/*Helper::saveClientLog('debit', 2, $body, $client_response);*/
		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function creditProcess(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'playerid', 'roundid', 'gamecode', 'platform', 'transid', 'currency', 'amount', 'reason', 'roundended')) {
				$http_status = 404;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
						];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details/* && $player_details != NULL*/) {

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
						];
				}
				else
				{
					if(!GameRound::check($json_data['roundid'])) {
						$http_status = 400;
						$response = [
							"errorcode" =>  "ROUND_ENDED",
							"errormessage" => "Game round have already been closed",
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
						
						$guzzle_response = $client->post($client_details->fund_transfer_url,
						    ['body' => json_encode(
						        	[
									  "access_token" => $client_details->client_access_token,
									  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									  "type" => "fundtransferrequest",
									  "datetsent" => "",
									  "gamedetails" => [
									    "gameid" => "",
									    "gamename" => ""
									  ],
									  "fundtransferrequest" => [
											"playerinfo" => [
												"client_player_id" => $client_details->client_player_id,
												"token" => $client_details->player_token
										],
										"fundinfo" => [
										      "gamesessionid" => "",
										      // "transactiontype" => "debit", // Barry
										      "transactiontype" => "credit", // RiAN TEST!
										      "transferid" => "",
										      "rollback" => "false",
										      "currencycode" => $client_details->currency,
										      "amount" => $json_data["amount"]
										]
									  ]
									]
						    )]
						);

						$client_response = json_decode($guzzle_response->getBody()->getContents());

						if(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "200") {

							if(array_key_exists("roundended", $json_data)) {
								if ($json_data["roundended"] == "true") {
									GameRound::end($json_data['roundid']);
								}
							}
							
							$game_details = Game::find($json_data["gamecode"]);

							$json_data['income'] = $json_data['amount'] - $json_data["amount"];

							GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

							$http_status = 200;
							$response = [
								"status" => "OK",
								"currency" => $client_details->currency,
								"balance" => $client_response->fundtransferresponse->balance,
							];
						}
					}
				}
			}
		}
		
		Helper::saveLog('credit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);
	}

	public function debitAndCreditProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'playerid', 'roundid', 'gamecode', 'platform', 'transid', 'currency', 'betamount', 'winamount', 'roundended')) {

				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
						];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details/* && $player_details != NULL*/) {
				GameRound::create($json_data['roundid'], $client_details->token_id);
				
				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
						];
				}
				else
				{
					if(!GameRound::find($json_data['roundid'])) {
					
						// If round is not found
						$http_status = 404;
						$response = [
							"errorcode" =>  "ROUND_NOT_FOUND",
							"errormessage" => "Round not found",
						];
					}
					else
					{
						if(!GameRound::check($json_data['roundid'])) {
							$http_status = 400;
							$response = [
								"errorcode" =>  "ROUND_ENDED",
								"errormessage" => "Game round have already been closed",
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
							
							$debit_guzzle_response = $client->post($client_details->fund_transfer_url,
							    ['body' => json_encode(
							        	[
										  "access_token" => $client_details->client_access_token,
										  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
										  "type" => "fundtransferrequest",
										  "datetsent" => "",
										  "gamedetails" => [
										    "gameid" => "",
										    "gamename" => ""
										  ],
										  "fundtransferrequest" => [
												"playerinfo" => [
													"client_player_id" => $client_details->client_player_id,
													"token" => $client_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "debit",
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $client_details->currency,
											      "amount" => $json_data["betamount"]
											]
										  ]
										]
							    )]
							);
							$debit_client_response = json_decode($debit_guzzle_response->getBody()->getContents());

							$credit_guzzle_response = $client->post($client_details->fund_transfer_url,
							    ['body' => json_encode(
							        	[
										  "access_token" => $client_details->client_access_token,
										  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
										  "type" => "fundtransferrequest",
										  "datetsent" => "",
										  "gamedetails" => [
										    "gameid" => "",
										    "gamename" => ""
										  ],
										  "fundtransferrequest" => [
												"playerinfo" => [
													"client_player_id" => $client_details->client_player_id,
													"token" => $client_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "credit",
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $client_details->currency,
											      "amount" => $json_data["winamount"]
											]
										  ]
										]
							    )]
							);

							$credit_client_response = json_decode($credit_guzzle_response->getBody()->getContents());

							if(isset($debit_client_response->fundtransferresponse->status->code) 
						&& $debit_client_response->fundtransferresponse->status->code == "402") {
								$http_status = 404;
								$response = [
									"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
									"errormessage" => "Not sufficient funds",
									"httpstatus" => "402"
								];
							}
							else
							{
								if(isset($credit_client_response->fundtransferresponse->status->code) 
							&& $credit_client_response->fundtransferresponse->status->code == "200") {

									if(array_key_exists("roundended", $json_data)) {
										if ($json_data["roundended"] == "true") {
											GameRound::end($json_data['roundid']);
										}
									}

									$game_details = Game::find($json_data["gamecode"]);
									$json_data["amount"] = $json_data["betamount"];
									$json_data['income'] = $json_data['betamount'];

									GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);
						
									$json_data["amount"] = $json_data["winamount"];
									$json_data["reason"] = "";
									
									GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);
									
									$response = [
										"status" => "OK",
										"currency" => $client_details->currency,
										"balance" => $credit_client_response->fundtransferresponse->balance,
									];
								}
							}
						}
					}
				}
			}
		}
		
		Helper::saveLog('debitandcredit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);
	}

	public function rollBackTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		if(!CallParameters::check_keys($json_data, 'playerid', 'roundid')) {
				$http_status = 400;
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
					];
		}
		else
		{
			$http_status = 404;
			$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
					];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details/* && $player_details != NULL*/) {
				// Check if round exist
				if(!GameRound::find($json_data['roundid'])) {
					
					// If round is not found
					$http_status = 404;
					$response = [
						"errorcode" =>  "ROUND_NOT_FOUND",
						"errormessage" => "Round not found",
					];
				}
				else
				{
					// If round is found
					// Check if "originaltransid" is present in the Solid Gaming request
					if(array_key_exists('originaltransid', $json_data)) {
						
						// Check if the transaction exist
						$game_transaction = GameTransaction::find($json_data['originaltransid']);

						// If transaction is not found
						if(!$game_transaction) {
							$http_status = 404;
							$response = [
								"errorcode" =>  "TRANS_NOT_FOUND",
								"errormessage" => "Transaction not found",
							];
						}
						else
						{
							// If transaction is found, send request to the client
							$client = new Client([
							    'headers' => [ 
							    	'Content-Type' => 'application/json',
							    	'Authorization' => 'Bearer '.$client_details->client_access_token
							    ]
							]);
							
							$guzzle_response = $client->post($client_details->fund_transfer_url,
							    ['body' => json_encode(
							        	[
										  "access_token" => $client_details->client_access_token,
										  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
										  "type" => "fundtransferrequest",
										  "datetsent" => "",
										  "gamedetails" => [
										    "gameid" => "",
										    "gamename" => ""
										  ],
										  "fundtransferrequest" => [
												"playerinfo" => [
													"client_player_id" => $client_details->client_player_id,
													"token" => $client_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "credit",
											      "transferid" => "",
											      "rollback" => "true",
											      "currencycode" => $client_details->currency,
											      "amount" => $game_transaction->bet_amount
											]
										  ]
										]
							    )]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							// If client returned a success response
							if($client_response->fundtransferresponse->status->code == "200") {
								$json_data['income'] = $game_transaction->bet_amount;
								GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $client_details);
								
								$http_status = 200;
								$response = [
									"status" => "OK",
									"currency" => $client_details->currency,
									"balance" => $client_response->fundtransferresponse->balance,
								];
							}
						}
					}
					else
					{
						// Check if "originaltransid" is not present in the Solid Gaming request
						// Check if the round is ended
						if(!GameRound::check($json_data['roundid'])) {
							// If round is ended
							$http_status = 400;
							$response = [
								"errorcode" =>  "ROUND_ENDED",
								"errormessage" => "Game round have already been closed",
							];
						}
						else
						{
							$http_status = 404;
							$response = [
								"errorcode" =>  "TRANS_NOT_FOUND",
								"errormessage" => "Transaction not found",
							];

							// If round is still active
							$bulk_rollback_result = GameTransaction::bulk_rollback($json_data['roundid']);

							if($bulk_rollback_result) {
								foreach ($bulk_rollback_result as $key => $value) {
									/*var_dump($value); die();*/
									$client = new Client([
									    'headers' => [ 
									    	'Content-Type' => 'application/json',
									    	'Authorization' => 'Bearer '.$client_details->client_access_token
									    ]
									]);
									
									$guzzle_response = $client->post($client_details->fund_transfer_url,
									    ['body' => json_encode(
									        	[
												  "access_token" => $client_details->client_access_token,
												  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
												  "type" => "fundtransferrequest",
												  "datetsent" => "",
												  "gamedetails" => [
												    "gameid" => "",
												    "gamename" => ""
												  ],
												  "fundtransferrequest" => [
														"playerinfo" => [
															"client_player_id" => $client_details->client_player_id,
															"token" => $client_details->player_token
													],
													"fundinfo" => [
													      "gamesessionid" => $value->round_id,
													      "transactiontype" => "credit",
													      "transferid" => $value->game_trans_id,
													      "rollback" => "true",
													      "currencycode" => $client_details->currency,
													      "amount" => $value->bet_amount
													]
												  ]
												]
									    )]
									);

									$client_response = json_decode($guzzle_response->getBody()->getContents());

									// If client returned a success response
									if($client_response->fundtransferresponse->status->code == "200") {
										$json_data['income'] = $value->bet_amount;
										GameTransaction::save('rollback', $json_data, $value, $client_details, $client_details);
									}

								}

								$http_status = 200;
								$response = [
										"status" => "OK",
										"currency" => $client_details->currency,
										"balance" => $client_response->fundtransferresponse->balance,
									];
							}

						}
						
					}					
				
				}
			}
		}

		Helper::saveLog('rollback', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function endPlayerRound(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		$http_status = 404;
		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
					];

		$client_details = $this->_getClientDetails('player_id', $json_data['playerid']);
		/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

		if ($client_details/* && $player_details != NULL*/) {
			if(!GameRound::check($json_data['roundid'])) {
				$http_status = 400;
				$response = [
					"errorcode" =>  "ROUND_ENDED",
					"errormessage" => "Game round have already been closed",
				];
			}
			else
			{
				
				if(array_key_exists("roundended", $json_data)) {
					if ($json_data["roundended"] == "true") {
						GameRound::end($json_data['roundid']);
					}
				}
				
				$http_status = 200;
				$response = [
					"status" => "OK",
					"currency" => $client_details->currency,
					"balance" => $client_response->fundtransferresponse->balance,
				];
			
			}
			
		}

		Helper::saveLog('rollback', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	/*private function _getClientDetails($client_code) {

		$query = DB::table("clients AS c")
				 ->select('c.client_id', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
				 ->where('client_code', $client_code);

				 $result= $query->first();

		return $result;
	}*/

	private function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id', 'p.username', 'p.email', 'p.language', 'c.default_currency AS currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

				 $result= $query->first();

		return $result;

	}


}
