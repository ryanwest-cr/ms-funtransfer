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
				$http_status = 401;
				$response = [
							"responseCode" =>  "REQUEST_DATA_FORMAT",
							"errorDescription" => "Data format of request not as expected."
						];
		}
		else
		{
			$http_status = 402;
			$response = [
							"responseCode" =>  "TOKEN_NOT_VALID",
							"errorDescription" => "Token provided in request not valid in Wallet."
						];
			
			$client_details = $this->_getClientDetails('token', $token);
			
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

		Helper::saveLog('authentication', 18, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	
	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$player_id = RouteParam::get($request, 'player_id');

		if(!CallParameters::check_keys($json_data, 'gameCode', 'sessionToken')) {
				$http_status = 401;
				$response = [
							"responseCode" =>  "REQUEST_DATA_FORMAT",
							"errorDescription" => "Data format of request not as expected."
						];
		}
		else
		{
			$http_status = 402;
			$response = [
							"responseCode" =>  "TOKEN_NOT_VALID",
							"errorDescription" => "Token provided in request not valid in Wallet."
						];

			// Find the player and client details
			$client_details = $this->_getClientDetails('player_id', $player_id);

			if ($client_details) {

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 18, $json_data['gameCode']);

				if(!$client_game_subscription) {
					$http_status = 403;
					$response = [
							"responseCode" =>  "PLAYER_FROZEN",
							"errorDescription" => "Player (to which token points) not in correct state to perform any actions."
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
									"datesent" => Helper::datesent(),
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

						$response = [
							"balance" => $this->to_pennies($client_response->playerdetailsresponse->balance)
						];
					}
				}
			}
		}

		Helper::saveLog('balance', 18, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function gameTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		if(!CallParameters::check_keys($json_data, 'playerId', 'roundId', 'gameCode', 'roundAction', 'sessionToken')) {
				$http_status = 401;
				$response = [
							"responseCode" =>  "REQUEST_DATA_FORMAT",
							"errorDescription" => "Data format of request not as expected."
						];
		}
		else
		{
			$http_status = 402;
			$response = [
							"responseCode" =>  "TOKEN_NOT_VALID",
							"errorDescription" => "Token provided in request not valid in Wallet."
						];

			$client_details = $this->_getClientDetails('player_id', $json_data['playerId']);

			if ($client_details) {
				GameRound::create($json_data['roundId'], $client_details->token_id);

				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 18, $json_data['gameCode']);

				if(!$client_game_subscription) {
					$http_status = 403;
					$response = [
							"responseCode" =>  "PLAYER_FROZEN",
							"errorDescription" => "Player (to which token points) not in correct state to perform any actions."
						];
				}
				else
				{
					if(!GameRound::check($json_data['roundId'])) {
						$http_status = 405;
						$response = [
							"responseCode" =>  "ROUND_NOT_FOUND",
							"errorDescription" => "Round provided by Oryx Hub was not found in platform"
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
											  "datesent" => Helper::datesent(),
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
												      "transactiontype" => 'debit',
												      "transferid" => "",
												      "rollback" => "false",
												      "currencycode" => $client_details->currency,
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
										  "datesent" => Helper::datesent(),
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
											      "transactiontype" => $transactiontype,
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $client_details->currency,
											      "amount" => $json_data[$key]["amount"]
											]
										  ]
										]
							    )]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "402") {
								$http_status = 200;
								$response = [
									"responseCode" =>  "OUT_OF_MONEY",
									"errorDescription" => "Player ran out of money."
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
									$json_data['income'] = $json_data[$key]['amount'];

									$game_details = Game::find($json_data["gameCode"]);
									GameTransaction::save($transactiontype, $json_data, $game_details, $client_details, $client_details);

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
		
		Helper::saveLog($transactiontype, 18, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

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

	private function _isIdempotent($transaction_id, $is_rollback = false) {
		$result = false;
		$query = DB::table('game_transaction_ext')
								->where('provider_trans_id', $transaction_id);
		if ($is_rollback == true) {
					$query->where([
				 		["game_transaction_type", "=", 3]
				 	]);
				}

		$transaction_exist = $query->first();

		if($transaction_exist) {
			$result = $transaction_exist;
		}

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
