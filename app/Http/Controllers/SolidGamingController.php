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
			
			$client_details = ProviderHelper::getClientDetails('token', $json_data['token']);
			
			if ($client_details) {
				/*$client = new Client([
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
				
				$client_response = json_decode($guzzle_response->getBody()->getContents());*/

				$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
				
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
						"currency" => $client_details->default_currency,
						"balance" => $client_response->playerdetailsresponse->balance,
						"testaccount" => ($client_details->test_player ? true : false),
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


		Helper::saveLog('solid_authentication', 2, file_get_contents("php://input"), $response);
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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details) {
				/*$client = new Client([
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
									"gamelaunch" => "true"
								]]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());*/

				$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);		
				
				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					$http_status = 200;
					$response = [
						"status" => "OK",
						"brand" => 'BETRNKMW',
						"currency" => $client_details->default_currency,
						"testaccount" => ($client_details->test_player ? true : false),
						"country" => "",
						"affiliatecode" => "",
						"displayname" => $client_response->playerdetailsresponse->accountname,
					];
				}
			}
		}

		Helper::saveLog('solid_playerdetails', 2, file_get_contents("php://input"), $response);
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
			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/
			
			if ($client_details) {

				// Check if the game is available for the client
				/*$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
							"httpstatus" => "404"
						];
				}
				else
				{*/
					/*$client = new Client([
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

					$client_response = json_decode($guzzle_response->getBody()->getContents());*/

					$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
					
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

						$http_status = 200;
						$response = [
							"status" => "OK",
							"currency" => $client_details->default_currency,
							"balance" => $client_response->playerdetailsresponse->balance,
						];
					}
				/*}*/
			}
		}

		Helper::saveLog('solid_balance', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function debitProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$body = [];
		$response = [];
		$client_response = [];

		if($this->_isIdempotent($json_data['transid'])) {
			return $this->_isIdempotent($json_data['transid'])->mw_response;
		}

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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details) {
				GameRound::create($json_data['roundid'], $client_details->token_id);

				// Check if the game is available for the client
				/*$subscription = new GameSubscription();
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
				{*/
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
						$json_data['income'] = $json_data['amount'];

						$game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

						$game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

						$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['amount'], 1);

						// change $json_data['roundid'] to $game_transaction_id
		                $client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'debit');

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

								$http_status = 200;
								$response = [
									"status" => "OK",
									"currency" => $client_details->default_currency,
									"balance" => $client_response->fundtransferresponse->balance,
								];
							}
						}

						ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
					}
				/*}*/
			}
		}
		
		
		Helper::saveLog('solid_debit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function creditProcess(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$body = [];
		$response = [];
		$client_response = [];

		if($this->_isIdempotent($json_data['transid'])) {
			return $this->_isIdempotent($json_data['transid'])->mw_response;
		}

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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details/* && $player_details != NULL*/) {

				// Check if the game is available for the client
				/*$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
						];
				}
				else
				{*/
					if(!GameRound::check($json_data['roundid'])) {
						$http_status = 400;
						$response = [
							"errorcode" =>  "ROUND_ENDED",
							"errormessage" => "Game round have already been closed",
						];
					}
					else
					{
						$game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

						$json_data['income'] = $json_data["amount"];

						$game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

						$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['amount'], 2);

						// change $json_data['roundid'] to $game_transaction_id
               			$client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit');

						if(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "200") {

							if(array_key_exists("roundended", $json_data)) {
								if ($json_data["roundended"] == "true") {
									GameRound::end($json_data['roundid']);
								}
							}
							
							$http_status = 200;
							$response = [
								"status" => "OK",
								"currency" => $client_details->default_currency,
								"balance" => $client_response->fundtransferresponse->balance,
							];
						}

						ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
					}
				/*}*/
			}
		}
		
		
		Helper::saveLog('solid_credit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);
	}

	public function debitAndCreditProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$body = [];
		$response = [];
		$client_response = [];

		if($this->_isIdempotent($json_data['transid'])) {
			return $this->_isIdempotent($json_data['transid'])->mw_response;
		}

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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			/*$player_details = PlayerHelper::getPlayerDetails($json_data['playerid']);*/

			if ($client_details/* && $player_details != NULL*/) {
				GameRound::create($json_data['roundid'], $client_details->token_id);
				
				// Check if the game is available for the client
				$subscription = new GameSubscription();
				$client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				/*if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
						];
				}
				else
				{*/
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
							/*DEBIT*/

							$json_data['income'] = $json_data['betamount'];
							$json_data['amount'] = $json_data['betamount'];

							$game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

							$debit_game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

							$debit_game_trans_ext_id = ProviderHelper::createGameTransExtV2($debit_game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['betamount'], 1);

							// change $json_data['roundid'] to $debit_game_transaction_id
			                $debit_client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['betamount'], $game_details->game_code, $game_details->game_name, $debit_game_trans_ext_id, $debit_game_transaction_id, 'debit');

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
								$response = [
										"status" => "OK",
										"currency" => $client_details->default_currency,
										"balance" => $debit_client_response->fundtransferresponse->balance,
									];

								ProviderHelper::updatecreateGameTransExt($debit_game_trans_ext_id, $json_data, $response, $debit_client_response->requestoclient, $debit_client_response, $json_data);

								/*CREDIT*/

								$game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

								$json_data['income'] = $json_data["winamount"];
								$json_data['amount'] = $json_data["winamount"];

								$credit_game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

								$credit_game_trans_ext_id = ProviderHelper::createGameTransExtV2($credit_game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['winamount'], 2);

								// change $json_data['roundid'] to $credit_game_transaction_id
		               			$credit_client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['winamount'], $game_details->game_code, $game_details->game_name, $credit_game_trans_ext_id, $credit_game_transaction_id, 'credit');

								if(isset($credit_client_response->fundtransferresponse->status->code) 
							&& $credit_client_response->fundtransferresponse->status->code == "200") {

									if(array_key_exists("roundended", $json_data)) {
										if ($json_data["roundended"] == "true") {
											GameRound::end($json_data['roundid']);
										}
									}
									
									$response = [
										"status" => "OK",
										"currency" => $client_details->default_currency,
										"balance" => $credit_client_response->fundtransferresponse->balance,
									];

								}
							}
				
							ProviderHelper::updatecreateGameTransExt($credit_game_trans_ext_id, $json_data, $response, $credit_client_response->requestoclient, $credit_client_response, $json_data);
						}
					}
				/*}*/
			}
		}
		
		Helper::saveLog('solid_debitandcredit', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);
	}

	public function rollBackTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$body = [];
		$response = [];
		$client_response = [];

		if(array_key_exists('originaltransid', $json_data)) {
			if($this->_isIdempotent($json_data['originaltransid'], true)) {
				return $this->_isIdempotent($json_data['originaltransid'], true)->mw_response;
			}
		}
		
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

			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);

			if ($client_details) {
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

							$json_data['transid'] = $json_data['originaltransid'];
							$json_data['income'] = 0;

							// Find game details by transaction id
							$game_details = Game::findby('trans_id', $json_data["originaltransid"], config("providerlinks.solid.PROVIDER_ID"));

							// if refund is not exisiting, create one
							$game_transaction_id = GameTransaction::find_refund($json_data["originaltransid"]);

							if(!$game_transaction_id) {
								$game_transaction_id = GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $client_details);
							}

							$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transid'], $json_data['roundid'], $game_transaction->bet_amount, 3);

							// change $json_data['roundid'] to $game_transaction_id
	               			$client_response = ClientRequestHelper::fundTransfer($client_details, $game_transaction->bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);

							// If client returned a success response
							if($client_response->fundtransferresponse->status->code == "200") {
								$http_status = 200;
								$response = [
									"status" => "OK",
									"currency" => $client_details->default_currency,
									"balance" => $client_response->fundtransferresponse->balance,
								];
							}

							ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
						
							// Check if round should be ended
							if(array_key_exists("roundended", $json_data)) {
								if ($json_data["roundended"] == "true") {
									GameRound::end($json_data['roundid']);
								}
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

									$json_data['transid'] = $value->game_trans_id;
									$json_data['income'] = 0;

									// Find game details by transaction id
									$game_details = Game::findby('round_id', $json_data["roundid"], config("providerlinks.solid.PROVIDER_ID"));
									
									$game_transaction_id = GameTransaction::save('rollback', $json_data, $value, $client_details, $client_details);

									$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $value->game_trans_id, $value->round_id, $value->bet_amount, 3);

									// change $json_data['roundid'] to $game_transaction_id
			               			$client_response = ClientRequestHelper::fundTransfer($client_details, $value->bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);

									// If client returned a success response
									if($client_response->fundtransferresponse->status->code == "200") {
										$http_status = 200;
										$response = [
											"status" => "OK",
											"currency" => $client_details->default_currency,
											"balance" => $client_response->fundtransferresponse->balance,
										];
									}

									ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
								
									// Check if round should be ended
									if(array_key_exists("roundended", $json_data)) {
										if ($json_data["roundended"] == "true") {
											GameRound::end($json_data['roundid']);
										}
									}

								}
							}
						}
					}					
				}
			}
		}

		Helper::saveLog('solid_rollback', 2, file_get_contents("php://input"), $response);
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

		$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
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

				$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
				
				$http_status = 200;
				$response = [
					"status" => "OK",
					"currency" => $client_details->default_currency,
					"balance" => $client_response->playerdetailsresponse->balance,
				];
			
			}
			
		}

		Helper::saveLog('solid_endplayer', 2, file_get_contents("php://input"), $response);
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
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id', 'p.username', 'p.email', 'p.language', 'p.test_player', 'c.default_currency', 'c.default_currency AS currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
				 		["game_transaction_type", "=", 3],
				 		["mw_response", "NOT LIKE", "%FAILED%"]
				 	]);
				}

		$transaction_exist = $query->first();

		if($transaction_exist) {
			$result = $transaction_exist;
		}

		return $result;								
	}

}
