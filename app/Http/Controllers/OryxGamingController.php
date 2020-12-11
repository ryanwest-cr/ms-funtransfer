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
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;

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
			
			$client_details = ProviderHelper::getClientDetails('token', $token);
			
			if ($client_details) {

				$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
			
				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					// save player details if not exist
					$player_id = PlayerHelper::saveIfNotExist($client_details, $client_response);

					// save token to system if not exist
					TokenHelper::saveIfNotExist($player_id, $token);

					$http_status = 200;
					$response = [
						"playerId" => "$player_id",
						// "currencyCode" => "USD", // RiAN
						"currencyCode" => $client_details->default_currency, 
						"languageCode" => "ENG",
						"balance" => $this->_toPennies($client_response->playerdetailsresponse->balance),
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

		Helper::saveLog('oryx_authentication', 18, file_get_contents("php://input"), $response);
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
			$client_details = ProviderHelper::getClientDetails('player_id', $player_id);

			if ($client_details) {
					$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
					
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {
						$http_status = 200;
						$response = [
							"balance" => $this->_toPennies($client_response->playerdetailsresponse->balance)
						];
					}
				/*}*/
			}
		}

		Helper::saveLog('oryx_balance', 18, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function gameTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		if (array_key_exists('bet', $json_data) || array_key_exists('win', $json_data)) {
			$transaction_id = (array_key_exists('bet', $json_data) == true ? $json_data['bet']['transactionId'] : $json_data['win']['transactionId']);

			if($this->_isCancelled($transaction_id)) {
				$playerdetails_response = Providerhelper::playerDetailsCall($json_data['sessionToken']);
				$http_status = 501;

				$response = [
					"responseCode" => "ERROR",
					"balance" => $this->_toPennies($playerdetails_response->playerdetailsresponse->balance),
				];

				return response()->json($response, $http_status);
			}

			if($this->_isIdempotent($transaction_id)) {
				return $this->_isIdempotent($transaction_id)->mw_response;
			}
		}
		

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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerId']);

			if ($client_details) {
				// Check if the game is available for the client
				/*$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 18, $json_data['gameCode']);

				if(!$client_game_subscription) {
					$http_status = 403;
					$response = [
							"responseCode" =>  "PLAYER_FROZEN",
							"errorDescription" => "Player (to which token points) not in correct state to perform any actions."
						];
				}
				else
				{*/
					/*if(!GameRound::find($json_data['roundId'])) {
						$http_status = 405;
						$response = [
							"responseCode" =>  "ROUND_NOT_FOUND",
							"errorDescription" => "Round provided by Oryx Hub was not found in platform"
						];
					}
					else
					{*/
						if(array_key_exists('bet', $json_data) || array_key_exists('win', $json_data)) {

							GameRound::create($json_data['roundId'], $client_details->token_id);

							if(array_key_exists('bet', $json_data)) {

								// check if this is a free round
								if(array_key_exists('freeRoundId', $json_data)) {
									$amount = 0;
									$json_data['amount'] = 0;
									$json_data['income'] = 0;

									$json_data['free_round_data'] = ['free_round_id' => $json_data['freeRoundId']];
									if(array_key_exists('freeRoundExternalId', $json_data)) {
										$json_data['free_round_data']['free_round_external_id'] =  $json_data['freeRoundExternalId'];
									}

								}
								else
								{
									$amount = $this->_toDollars($json_data['bet']["amount"]);
									$json_data['amount'] = $this->_toDollars($json_data['bet']["amount"]);
									$json_data['income'] = $this->_toDollars($json_data['bet']["amount"]);
								}

								$json_data['roundid'] = $json_data['roundId'];
								$json_data['transid'] = $json_data['bet']['transactionId'];

								$game_details = Game::find($json_data["gameCode"], config("providerlinks.oryx.PROVIDER_ID"));
								
								$game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

								$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['bet']['transactionId'], $json_data['roundId'], $amount, 1);
								
								// change $json_data['roundId'] to $game_transaction_id
				                $client_response = ClientRequestHelper::fundTransfer($client_details, $amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'debit');
				                
								if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402") {
									$http_status = 200;
									$response = [
										"responseCode" =>  "OUT_OF_MONEY",
										"errorDescription" => "Player ran out of money.",
										"balance" => $this->_toPennies($client_response->fundtransferresponse->balance)
									];
								}
								else
								{
									if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") {

										if(array_key_exists("roundAction", $json_data)) {
											if ($json_data["roundAction"] == "CLOSE") {
												GameRound::end($json_data['roundId']);
											}
										}

										$http_status = 200;
										$response = [
											"responseCode" => "OK",
											"balance" => $this->_toPennies($client_response->fundtransferresponse->balance),
										];

									}
								}

								ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);

								if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402") {
									// return if bet is not successful
									return response()->json($response, $http_status);
								}
							}

							if(array_key_exists('win', $json_data)) {

								$game_details = Game::find($json_data["gameCode"], config("providerlinks.oryx.PROVIDER_ID"));
								
								$jackpot_amount = (array_key_exists('jackpotAmount', $json_data['win']) ? $this->_toDollars($json_data['win']['jackpotAmount']) : 0);

								$json_data['amount'] = $this->_toDollars($json_data['win']["amount"]) + $jackpot_amount;
								$json_data['income'] = $this->_toDollars($json_data['win']["amount"]) + $jackpot_amount;
								$json_data['roundid'] = $json_data['roundId'];
								$json_data['transid'] = $json_data['win']['transactionId'];
								
								$game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

								$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['win']['transactionId'], $json_data['roundId'], $this->_toDollars($json_data['win']["amount"]) + $jackpot_amount, 2);

								// change $json_data['roundId'] to $game_transaction_id
		               			$client_response = ClientRequestHelper::fundTransfer($client_details, $this->_toDollars($json_data['win']["amount"]) + $jackpot_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit');

								if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402") {
									$http_status = 200;
									$response = [
										"responseCode" =>  "OUT_OF_MONEY",
										"errorDescription" => "Player ran out of money.",
										"balance" => $this->_toPennies($client_response->fundtransferresponse->balance)
									];
								}
								else
								{
									if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") {
										if(array_key_exists("roundAction", $json_data)) {
											if ($json_data["roundAction"] == "CLOSE") {
												GameRound::end($json_data['roundId']);
											}
										}

										$http_status = 200;
										$response = [
											"responseCode" => "OK",
											"balance" => $this->_toPennies($client_response->fundtransferresponse->balance),
										];
									}
								}
								
								ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
							}
						}
						else
						{
							if(GameRound::find($json_data['roundId']) == false) {
								$http_status = 405;
								$response = [
									"responseCode" =>  "ROUND_NOT_FOUND",
									"errorDescription" => "Round provided by Oryx Hub was not found in platform"
								];
							}
							else
							{
								$transactiontype = 'rollback';
								if(array_key_exists("roundAction", $json_data)) {
									
									if ($json_data["roundAction"] == "CLOSE") {
										GameRound::end($json_data['roundId']);
										
										$client_response = Providerhelper::playerDetailsCall($client_details->player_token);

										if(isset($client_response->playerdetailsresponse->status->code) 
											&& $client_response->playerdetailsresponse->status->code == "200") {

											$http_status = 200;
											$response = [
												"responseCode" => "OK",
												"balance" => $this->_toPennies($client_response->playerdetailsresponse->balance),
											];
										}

									}

									if ($json_data["roundAction"] == "CANCEL") {
										$bulk_rollback_result = GameTransaction::bulk_rollback($json_data['roundId']);
										
										if($bulk_rollback_result) {
											foreach ($bulk_rollback_result as $key => $value) {
												
												$json_data['roundid'] = $value->round_id;
												$json_data['income'] = 0;
												$json_data['transid'] = $value->game_trans_id;
												
												// Find game details by transaction id
												$game_details = Game::findby('round_id', $value->round_id, config("providerlinks.oryx.PROVIDER_ID"));
												
												$game_transaction_id = GameTransaction::rollbackTransaction($value->provider_trans_id);

												$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $value->provider_trans_id, $value->round_id, $value->bet_amount, 3);

												// change $json_data['roundId'] to $game_transaction_id
						               			$client_response = ClientRequestHelper::fundTransfer($client_details, $value->bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);
												

												if(isset($client_response->fundtransferresponse->status->code) 
											&& $client_response->fundtransferresponse->status->code == "200") {

													if(array_key_exists("roundAction", $json_data)) {
														if ($json_data["roundAction"] == "CLOSE") {
															GameRound::end($json_data['roundId']);
														}
													}

													$http_status = 200;
													$response = [
														"responseCode" => "OK",
														"balance" => $this->_toPennies($client_response->fundtransferresponse->balance),
													];
												}

												ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
											}
										}
									}
								}
							}
						}
					/*}*/
				/*}*/
			}
		}
		
		/*Helper::saveLog($transactiontype, 18, file_get_contents("php://input"), $response);*/
		return response()->json($response, $http_status);

	}

	public function gameTransactionV2(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		if($this->_isIdempotent($json_data['transactionId'], true)) {
			$http_status = 409;
				$response = [
							"responseCode" =>  "ERROR",
							"errorDescription" => "This transaction is already processed."
						];

			return response()->json($response, $http_status);
		}

		if(!CallParameters::check_keys($json_data, 'playerId', 'gameCode', 'action', 'sessionToken')) {
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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerId']);

			if ($client_details) {

				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);

				if ($json_data["action"] == "CANCEL") {
					$game_transaction = GameTransaction::find($json_data['transactionId']);

					// If transaction is not found
					if(!$game_transaction) {
						$http_status = 408;
						$response = [
										"responseCode" =>  "TRANSACTION_NOT_FOUND",
										"errorDescription" => "Transaction provided by Oryx Hub was not found in platform (interesting for TransactionChange method)"
									];

						//GENERATE A CANCELLED TRANSACTION
						$json_data['roundid'] = $json_data['roundId'];
						$json_data['transid'] = $json_data['transactionId'];
						$json_data['amount'] = 0;
						$json_data['reason'] = 'Generated cancelled transaction (ORYX)';
						$json_data['income'] = 0;

						$game_details = Game::find($json_data["gameCode"], config("providerlinks.oryx.PROVIDER_ID"));
						$game_transaction_id = GameTransaction::save('cancelled', $json_data, $game_details, $client_details, $client_details);
					
						$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transactionId'], $json_data['roundId'], 0, 3);
					}
					else
					{
						// If transaction is found, send request to the client
						$json_data['roundid'] = $game_transaction->round_id;
						$json_data['income'] = 0;
						$json_data['transid'] = $game_transaction->game_trans_id;

						$game_details = Game::find($json_data["gameCode"], config("providerlinks.oryx.PROVIDER_ID"));
						
						$game_transaction_id = GameTransaction::rollbackTransaction($json_data['transactionId']);
						
						$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $game_transaction->provider_trans_id, $game_transaction->round_id, $game_transaction->bet_amount, 3);
						
						// change $json_data['roundId'] to $game_transaction_id
               			$client_response = ClientRequestHelper::fundTransfer($client_details, $game_transaction->bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);
               			
						// If client returned a success response
						if($client_response->fundtransferresponse->status->code == "200") {
		
							$http_status = 200;
								$response = [
									"responseCode" => "OK",
									"balance" => $this->_toPennies($client_response->fundtransferresponse->balance),
								];

						}

						ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
					}
				}
				
			}
		}
		
		return response()->json($response, $http_status);

	}

	public function roundFinished(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$player_id = RouteParam::get($request, 'player_id');

		if(!CallParameters::check_keys($json_data, 'freeRoundId', 'playerId')) {
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
			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerId']);

			if ($client_details) {
					$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
					
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {
						$http_status = 200;
						$response = [
							"responseCode" => "OK",
							"balance" => $this->_toPennies($client_response->playerdetailsresponse->balance)
						];
					}
				/*}*/
			}
		}

		Helper::saveLog('oryx_round_finish', 18, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}


	private function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id', 'p.username', 'p.email', 'p.language', 'c.default_currency', 'c.default_currency AS currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

	private function _isCancelled($transaction_id, $is_rollback = false) {
		$result = false;
		$query = DB::table('game_transactions')
								->where('provider_trans_id', $transaction_id)
								->where('entry_id', 3);

		$transaction_cancelled = $query->first();

		if($transaction_cancelled) {
			$result = $transaction_cancelled;
		}

		return $result;								
	}

	private function _toPennies($value)
	{
	    return (float) str_replace(' ', '', intval(
	        strval(floatval(
	            preg_replace("/[^0-9.]/", "", $value)
	        ) * 100)
	    ));
	}

	private function _toDollars($value)
	{
		return (float) str_replace(' ', '', number_format(($value / 100), 2, '.', ' '));
	}

}
