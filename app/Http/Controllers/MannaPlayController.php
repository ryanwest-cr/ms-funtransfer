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

class MannaPlayController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId')) {
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
			$player_details = PlayerHelper::getPlayerDetails($json_data['account'], 'username');

			if ($client_details) {

				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
			        

			/*	var_dump(json_encode(
                                                [
                                                        "access_token" => $client_details->client_access_token,
                                                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                                                "type" => "playerdetailsrequest",
                                                                "datesent" => "",
                                                                "gamecode" => "",
                                                                "clientid" => $client_details->client_id,
                                                                "playerdetailsrequest" => [
                                                                        "token" => $player_details->player_token,
                                                                        "gamelaunch" => "false"
                                                                ]]
							)); die();
			 */
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode(
				        	[
				        		"access_token" => $client_details->client_access_token,
								"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
								"type" => "playerdetailsrequest",
								"datesent" => "",
								"gamecode" => "",
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
						"balance" => $client_response->playerdetailsresponse->balance,
						"Message" => "OK",
						"Code" => $client_response->playerdetailsresponse->currencycode,
						
					];
				}
			
			}
		}

		Helper::saveLog('balance', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function debitProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {

				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			if($api_key != env('MANNA_API_KEY')) {
				$response = [
							"errorcode" =>  "API_KEY_INVALID",
							"errormessage" => "API key invalid.",
							"httpstatus" => "404"
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
				$player_details = PlayerHelper::getPlayerDetails($json_data['account'], 'username');

				if ($client_details && $player_details != NULL) {
					GameRound::create($json_data['round_id'], $player_details->token_id);

					// Check if the game is available for the client
					$subscription = new GameSubscription();
					$client_game_subscription = $subscription->check($client_details->client_id, 6, $json_data['game_id']);

					if(!$client_game_subscription) {
						$response = [
								"errorcode" =>  "GAME_NOT_FOUND",
								"errormessage" => "Game not found",
								"httpstatus" => "404"
							];
					}
					else
					{
						if(!GameRound::check($json_data['round_id'])) {
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
												"token" => $player_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "debit",
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $player_details->currency,
											      "amount" => "-".$json_data["amount"]
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

									$json_data['income'] = $json_data['amount'];
									$json_data['roundid'] = $json_data['round_id'];
									$json_data['transid'] = $json_data['transaction_id'];

									$game_details = Game::find($json_data["game_id"]);
									GameTransaction::save('debit', $json_data, $game_details, $client_details, $player_details);

									$response = [
										"transaction_id" => $json_data['transaction_id'],
										"balance" => $client_response->fundtransferresponse->balance,
										"Message" => "OK",
										"Code" => $client_response->fundtransferresponse->currencycode,
									];
								}
							}
						}
					}
				}

				Helper::saveClientLog('debit', 2, $body, $client_response);
			}
		}
		
		
		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function creditProcess(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {

				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			if($api_key != env('MANNA_API_KEY')) {
				$response = [
							"errorcode" =>  "API_KEY_INVALID",
							"errormessage" => "API key invalid.",
							"httpstatus" => "404"
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
				$player_details = PlayerHelper::getPlayerDetails($json_data['account'], 'username');

				if ($client_details && $player_details != NULL) {

					// Check if the game is available for the client
					$subscription = new GameSubscription();
					$client_game_subscription = $subscription->check($client_details->client_id, 6, $json_data['game_id']);

					if(!$client_game_subscription) {
						$response = [
								"errorcode" =>  "GAME_NOT_FOUND",
								"errormessage" => "Game not found",
								"httpstatus" => "404"
							];
					}
					else
					{
						if(!GameRound::check($json_data['round_id'])) {
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
											      "transactiontype" => "debit",
											      "transferid" => "",
											      "rollback" => "false",
											      "currencycode" => $player_details->currency,
											      "amount" => $json_data["amount"]
											]
										  ]
										]
							    )]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "200") {
								
								$game_details = Game::find($json_data["game_id"]);

								$json_data['income'] = $json_data['amount'] - $json_data["amount"];
								$json_data['roundid'] = $json_data['round_id'];
								$json_data['transid'] = $json_data['transaction_id'];

								GameTransaction::update('credit', $json_data, $game_details, $client_details, $player_details);
								
								$response = [
									"transaction_id" => $json_data['transaction_id'],
									"balance" => $client_response->fundtransferresponse->balance,
									"Message" => "OK",
									"Code" => $client_response->fundtransferresponse->currencycode,
								];
							}
						}
					}
				}

			}
		}
		
		Helper::saveLog('credit', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function rollBackTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			if($api_key != env('MANNA_API_KEY')) {
				$response = [
							"errorcode" =>  "API_KEY_INVALID",
							"errormessage" => "API key invalid.",
							"httpstatus" => "404"
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
				$player_details = PlayerHelper::getPlayerDetails($json_data['sessionId'], 'token');

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
						if(array_key_exists('transaction_id', $json_data)) {
							
							// Check if the transaction exist
							$game_transaction = GameTransaction::find($json_data['transaction_id']);

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
										"transaction_id" => $json_data['transaction_id'],
										"balance" => $client_response->fundtransferresponse->balance,
										"Message" => "OK",
										"Code" => $client_response->fundtransferresponse->currencycode,
									];
								}
							}
						}
					}
				}
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

	/*private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

	}*/


}
