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

class OryxGamingController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function show(Request $request) { }

	public function authPlayer(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$token = RouteParam::get($request, 'token');

		if(!CallParameters::check_keys($json_data, 'gameCode')) {
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			$response = [
							"responseCode" =>  "TOKEN_NOT_FOUND",
							"errorDescription" => "Token provided in request not found in Wallet."
						];
			
			$client_details = $this->_getClientDetails($client_code);

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
									"token" => $token,
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
					TokenHelper::saveIfNotExist($player_id, $token);

					$response = [
						"playerId" => "$player_id",
						"currencyCode" => "USD",
						"languageCode" => "ENG",
						"balance" => $this->to_pennies($client_response->playerdetailsresponse->balance),
						"sessionToken" => $token
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
		echo json_encode($response);

	}

	
	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$player_id = RouteParam::get($request, 'player_id');

		if(!CallParameters::check_keys($json_data, 'gameCode', 'sessionToken')) {
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
							"httpstatus" => "404"
						];

			// Find the player and client details
			$client_details = $this->_getClientDetails($client_code);
			$player_details = PlayerHelper::getPlayerDetails($player_id);

			if ($client_details) {

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 3, $json_data['gameCode']);

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
										"token" => $player_details->player_token,
										"gamelaunch" => "false"
									]]
					    )]
					);

					$client_response = json_decode($guzzle_response->getBody()->getContents());
					
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

						$response = [
							"balance" => $this->to_pennies($client_response->playerdetailsresponse->balance)
						];
					}
				}
			}
		}

		Helper::saveLog('balance', 3, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function gameTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		if(!CallParameters::check_keys($json_data, 'playerId', 'roundId', 'gameCode', 'roundAction', 'sessionToken')) {

				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
							"httpstatus" => "404"
						];

			$client_details = $this->_getClientDetails($client_code);
			$player_details = PlayerHelper::getPlayerDetails($json_data['playerId']);

			if ($client_details && $player_details != NULL) {
				GameRound::create($json_data['roundId'], $player_details->token_id);

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 3, $json_data['gameCode']);

				if(!$client_game_subscription) {
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
							"httpstatus" => "404"
						];
				}
				else
				{
					if(!GameRound::check($json_data['roundId'])) {
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

						if(!array_key_exists('bet', $json_data) && !array_key_exists('win', $json_data)) {
							
							if(array_key_exists("roundAction", $json_data)) {
								if ($json_data["roundAction"] == "CLOSE") {
									GameRound::end($json_data['roundId']);
								}

								$transactiontype = 'close_round';

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
													"token" => $player_details->player_token
												],
												"fundinfo" => [
												      "gamesessionid" => "",
												      "transactiontype" => 'debit',
												      "transferid" => "",
												      "rollback" => "false",
												      "currencycode" => $player_details->currency,
												      "amount" => 0
												]
											  ]
											]
								    )]
								);

								$client_response = json_decode($guzzle_response->getBody()->getContents());

								if(isset($client_response->fundtransferresponse->status->code) 
							&& $client_response->fundtransferresponse->status->code == "200") {

									if(array_key_exists("roundAction", $json_data)) {
										if ($json_data["roundAction"] == "CLOSE") {
											GameRound::end($json_data['roundId']);
										}
									}

									$json_data['roundid'] = $json_data['roundId'];
									$json_data['transid'] = '';
									$json_data['amount'] = 0;
									$json_data['reason'] = NULL;

									$game_details = Game::find($json_data["gameCode"]);

									$response = [
										"responseCode" => "OK",
										"balance" => $this->to_pennies($client_response->fundtransferresponse->balance),
									];
								}
							}
						}
						else
						{
							$transactiontype = (array_key_exists('bet', $json_data) == true ? 'debit' : 'credit');
							$key = ($transactiontype == 'debit' ? "bet" : "win");

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
												"token" => $player_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => $transactiontype,
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $player_details->currency,
											      "amount" => /*($transactiontype == 'debit' ? "-" : "").*/$json_data[$key]["amount"]
											]
										  ]
										]
							    )]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "402") {
								$response = [
									"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
									"errormessage" => "Not sufficient funds",
									"httpstatus" => "402"
								];
							}
							else
							{
								if(isset($client_response->fundtransferresponse->status->code) 
							&& $client_response->fundtransferresponse->status->code == "200") {

									if(array_key_exists("roundAction", $json_data)) {
										if ($json_data["roundAction"] == "CLOSE") {
											GameRound::end($json_data['roundId']);
										}
									}

									$json_data['roundid'] = $json_data['roundId'];
									$json_data['transid'] = $json_data[$key]['transactionId'];
									$json_data['amount'] = $json_data[$key]['amount'];
									$json_data['reason'] = NULL;

									$game_details = Game::find($json_data["gameCode"]);
									GameTransaction::save($transactiontype, $json_data, $game_details, $client_details, $player_details);

									$response = [
										"responseCode" => "OK",
										"balance" => $this->to_pennies($client_response->fundtransferresponse->balance),
									];
								}
							}
						}
					}
				}
			}
		}
		
		Helper::saveLog($transactiontype, 3, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function rollBackTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		if(!CallParameters::check_keys($json_data, 'playerid', 'roundid')) {
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
						"httpstatus" => "404"
					];

			$client_details = $this->_getClientDetails($client_code);
			$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);

			if ($client_details && $player_details != NULL) {
				// Check if round exist
				if(!GameRound::find($json_data['roundid'])) {
					
					// If round is not found
					$response = [
						"errorcode" =>  "ROUND_NOT_FOUND",
						"errormessage" => "Round not found",
						"httpstatus" => "404"
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
							$response = [
								"errorcode" =>  "TRANS_NOT_FOUND",
								"errormessage" => "Transaction not found",
								"httpstatus" => "404"
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
												"token" => $player_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "credit",
											      "transferid" => "",
											      "rollback" => "true",
											      "currencycode" => $player_details->currency,
											      "amount" => $game_transaction->bet_amount
											]
										  ]
										]
							    )]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							// If client returned a success response
							if($client_response->fundtransferresponse->status->code == "200") {
								GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $player_details);
								
								$response = [
									"status" => "OK",
									"currency" => $client_response->fundtransferresponse->currencycode,
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
							$response = [
								"errorcode" =>  "ROUND_ENDED",
								"errormessage" => "Game round have already been closed",
								"httpstatus" => "404"
							];
						}
						else
						{
							$response = [
								"errorcode" =>  "TRANS_NOT_FOUND",
								"errormessage" => "Transaction not found",
								"httpstatus" => "404"
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
														"token" => $player_details->player_token
													],
													"fundinfo" => [
													      "gamesessionid" => $value->round_id,
													      "transactiontype" => "credit",
													      "transferid" => $value->game_trans_id,
													      "rollback" => "true",
													      "currencycode" => $player_details->currency,
													      "amount" => $value->bet_amount
													]
												  ]
												]
									    )]
									);

									$client_response = json_decode($guzzle_response->getBody()->getContents());

									// If client returned a success response
									if($client_response->fundtransferresponse->status->code == "200") {
										GameTransaction::save('rollback', $json_data, $value, $client_details, $player_details);
									}

								}

								$response = [
										"status" => "OK",
										"currency" => $client_response->fundtransferresponse->currencycode,
										"balance" => $client_response->fundtransferresponse->balance,
									];
							}

						}
						
					}					
				
				}
			}
		}

		Helper::saveLog('rollback', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function endPlayerRound(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
						"httpstatus" => "404"
					];

		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);

		if ($client_details && $player_details != NULL) {
			if(!GameRound::check($json_data['roundid'])) {
				$response = [
					"errorcode" =>  "ROUND_ENDED",
					"errormessage" => "Game round have already been closed",
					"httpstatus" => "404"
				];
			}
			else
			{
				
				if(array_key_exists("roundended", $json_data)) {
					if ($json_data["roundended"] == "true") {
						GameRound::end($json_data['roundid']);
					}
				}
				
				$response = [
					"status" => "OK",
					"currency" => $client_response->fundtransferresponse->currencycode,
					"balance" => $client_response->fundtransferresponse->balance,
				];
			
			}
			
		}

		Helper::saveLog('rollback', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	private function _getClientDetails($client_code) {

		$query = DB::table("clients AS c")
				 ->select('c.client_id', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
				 ->where('client_code', $client_code);

				 $result= $query->first();

		return $result;
	}

	private function to_pennies($value)
	{
	    return intval(
	        strval(floatval(
	            preg_replace("/[^0-9.]/", "", $value)
	        ) * 100)
	    );
	}

}
