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
	public $startTime;
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
		 $this->startTime = microtime(true);

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
				// TEMPORARY
				// $client_response = $this->playerDetailsCall($client_details);
				
				// if(isset($client_response->playerdetailsresponse->status->code) 
				// 	&& $client_response->playerdetailsresponse->status->code == "200") {

				// 	// save player details if not exist
				// 	$player_id = PlayerHelper::saveIfNotExist($client_details, $client_response);

				// 	// save token to system if not exist
				// 	TokenHelper::saveIfNotExist($player_id, $json_data["token"]);

				// 	$http_status = 200;
				// 	$response = [
				// 		"status" => "OK",
				// 		"brand" => 'BETRNKMW',
				// 		"playerid" => "$player_id",
				// 		"currency" => $client_details->default_currency,
				// 		"balance" => $client_response->playerdetailsresponse->balance,
				// 		"testaccount" => ($client_details->test_player ? true : false),
				// 		"wallettoken" => "",
				// 		"country" => "",
				// 		"affiliatecode" => "",
				// 		"displayname" => $client_response->playerdetailsresponse->accountname,
				// 	];
				// }
				// else
				// {
				// 	// change token status to expired
				// 	// TokenHelper::changeStatus($player_id, 'expired');
				// }
				// END OF TEMPORARY
					$http_status = 200;
					$response = [
						"status" => "OK",
						"brand" => 'BETRNKMW',
						"playerid" => $client_details->player_id,
						"currency" => $client_details->default_currency,
						"balance" => "999.10",
						"testaccount" => ($client_details->test_player ? true : false),
						"wallettoken" => "",
						"country" => "",
						"affiliatecode" => "",
						"displayname" => $client_details->username,
					];

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

				$client_response = $this->playerDetailsCall($client_details);		
				
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
	// BALANCE UPDATED 2020/11/17
	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/

		// if(!CallParameters::check_keys($json_data, 'playerid', 'gamecode', 'platform')) {
		// 		$http_status = 400;
		// 		$response = [
		// 				"errorcode" =>  "BAD_REQUEST",
		// 				"errormessage" => "The request was invalid.",
		// 			];
		// }
		// else
		// {
			$http_status = 404;
			$response = [
							"errorcode" =>  "PLAYER_NOT_FOUND",
							"errormessage" => "Player not found",
						];

			// Find the player and client details
			$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
			
			if ($client_details) {
					// $client_response = $this->playerDetailsCall($client_details);
					// if(isset($client_response->playerdetailsresponse->status->code) 
					// && $client_response->playerdetailsresponse->status->code == "200") {

						$http_status = 200;
						$response = [
							"status" => "OK",
							"currency" => $client_details->default_currency,
							"balance" => "999.10"
						];
					// }
				/*}*/
			}
		//}

		Helper::saveLog('SOLID_GAMING_BALANCE', 2, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}


	public function debitProcess(Request $request) 
	{

		$startTime = microtime(true);
		$time_receive = [
			"time_receive" => date('Y-m-d H:i:s.') . gettimeofday()['usec']
		];
		Helper::saveLog('SOLID_GAMING_HIT', 2, json_encode($time_receive), "DEBIT");
		

		$json_data = json_decode(file_get_contents("php://input"), true);

		if($this->_isIdempotent($json_data['transid'])) {
			return  $this->_isIdempotent($json_data['transid'])->mw_response;
		}

		$http_status = 404;
		$response = [
			"errorcode" =>  "PLAYER_NOT_FOUND",
			"errormessage" => "Player not found",
		];

		$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);

		if ($client_details) {
			//CREATE ROUND
			$this->create_Check($json_data['roundid'], $client_details->token_id);

			$json_data['income'] = $json_data['amount'];
			$game_details = $this->findGameDetails($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));
			$game_transaction_id = $this->saveGameTransaction('debit', $json_data, $game_details,$client_details->player_token);
			$game_trans_ext_id = $this->createGameTransExt($game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['amount'], 1);

   //          $client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'debit');

			// if(isset($client_response->fundtransferresponse->status->code) 
			// 	&& $client_response->fundtransferresponse->status->code == "200") {
			// 	$http_status = 200;
			// 	$response = [
			// 		"status" => "OK",
			// 		"currency" => $client_details->default_currency,
			// 		"balance" => $client_response->fundtransferresponse->balance,
			// 	];
			// }
			// ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
			$balance = 999.10 - $json_data["amount"];
			$http_status = 200;
				$response = [
					"status" => "OK",
					"currency" => $client_details->default_currency,
					"balance" => $balance,
				];
			ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $response, $response, $json_data);
		}
		Helper::saveLog('SOLID_GAMING_DEBIT', 2, file_get_contents("php://input"), $response);
		// $reponse_time = [
		// 	"type"=>"BETPROCESS",
		// 	"startTime"=>$this->startTime,
		// 	"endTime"=> microtime(true),
		// 	"response"=>microtime(true) - $this->startTime 
		// ];

		$time_response = [
			"time_response" => date('Y-m-d H:i:s.') . gettimeofday()['usec']
		];
		$total_process = microtime(true) - $startTime;
		Helper::saveLog('SOLID_RESONSE_TIME_DEBIT', 2, json_encode($time_response), ["process_time" => $total_process] );
		return response()->json($response, $http_status);

	}

	public function creditProcess(Request $request)
	{
		$startTime = microtime(true);
		$time_receive = [
			"time_receive" => date('Y-m-d H:i:s.') . gettimeofday()['usec']
		];
		Helper::saveLog('SOLID_GAMING_HIT', 2, json_encode($time_receive), "CREDIT");

		$json_data = json_decode(file_get_contents("php://input"), true);
		// $is_idempotent = $this->_isIdempotent($json_data['transid']);
		if($this->_isIdempotent($json_data['transid'])) {
			Helper::saveLog('SOLID_GAMING_CREDIT_IDOM', 2, file_get_contents("php://input"),"");
			return $this->_isIdempotent($json_data['transid'])->mw_response;

		}
		// if(!CallParameters::check_keys($json_data, 'playerid', 'roundid', 'gamecode', 'platform', 'transid', 'currency', 'amount', 'reason', 'roundended')) {
		// 		$http_status = 404;
		// 		$response = [
		// 			"errorcode" =>  "BAD_REQUEST",
		// 			"errormessage" => "The request was invalid.",
		// 		];
		// }
		// else
		// {
		
			try{
				$http_status = 404;
				$response = [
					"errorcode" =>  "PLAYER_NOT_FOUND",
					"errormessage" => "Player not found",
				];
				$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerid']);
				if ($client_details) 
				{
					//CREATE ROUND
					// $this->create_Check($json_data['roundid'], $client_details->token_id);

					// if (!$this->create_Check($json_data['roundid'], $client_details->token_id)) {
					// 	$http_status = 400;
					// 	$response = [
					// 		"errorcode" =>  "ROUND_ENDED",
					// 		"errormessage" => "Game round have already been closed",
					// 	];
					// 	Helper::saveLog('SOLID_GAMING_CREDIT_ROUND', 2, file_get_contents("php://input"), $response);
					// 	return response()->json($response, $http_status);
					// }
					// If free round create round id
					// if(isset($json_data['payoutreason'])) {
					// 	if($json_data['payoutreason'] == 'FREEROUND_WIN') {
					// 		$this->create_Check($json_data['roundid'], $client_details->token_id);
					// 	}
					// }

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
					// $exist_round_create = $this->create_Check($json_data['roundid'], $client_details->token_id);
					// if (!$this->create_Check($json_data['roundid'], $client_details->token_id)) {
					// 	$http_status = 400;
					// 	$response = [
					// 		"errorcode" =>  "ROUND_ENDED",
					// 		"errormessage" => "Game round have already been closed",
					// 	];

					// }
					// else
					// {
						$game_details = $this->findGameDetails($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));
						$json_data['income'] = $json_data["amount"];
						if(isset($json_data['payoutreason'])) {
							if($json_data['payoutreason'] == 'FREEROUND_WIN') {
								// $game_transaction_id = GameTransaction::save('credit', $json_data, $game_details, $client_details, $client_details);
								$game_transaction_id = $this->saveGameTransaction('debit', $json_data, $game_details,$client_details->player_token);
							}
						}
						else
						{
							$game_transaction_id = $this->update($json_data);
						}
						// $game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['amount'], 2);
						$game_trans_ext_id = $this->createGameTransExt($game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['amount'], 2);

	     //       			$client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit');
						// if(isset($client_response->fundtransferresponse->status->code) 
						// 	&& $client_response->fundtransferresponse->status->code == "200") {
						// 	// if(array_key_exists("roundended", $json_data)) {
						// 	// 	if ($json_data["roundended"] == "true") {
						// 	// 		GameRound::end($json_data['roundid']); //UPDATE
						// 	// 	}
						// 	// }
						// 	$http_status = 200;
						// 	$response = [
						// 		"status" => "OK",
						// 		"currency" => $client_details->default_currency,
						// 		"balance" => $client_response->fundtransferresponse->balance,
						// 	];
						// }
						// ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);

						$balance = 999.10 + $json_data["amount"];
						$http_status = 200;
							$response = [
								"status" => "OK",
								"currency" => $client_details->default_currency,
								"balance" => $balance,
							];

						ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $response, $response, $json_data);
				}
				Helper::saveLog('SOLID_GAMING_CREDIT', 2, file_get_contents("php://input"), $response);

				// $reponse_time = [
				// 	"type"=>"CREDITPROCESS",
				// 	"startTime"=>$this->startTime,
				// 	"endTime"=> microtime(true),
				// 	"response"=>microtime(true) - $this->startTime 
				// ];
				//  Helper::saveLog('SOLID_RESONSE_TIME_CREDIT', 2, json_encode($reponse_time), ["reponse_time" => microtime(true) - $this->startTime]);

				 $time_response = [
					"time_response" => date('Y-m-d H:i:s.') . gettimeofday()['usec']
				];
				$total_process = microtime(true) - $startTime;
				Helper::saveLog('SOLID_RESONSE_TIME_CREDIT', 2, json_encode($time_response), ["process_time" => $total_process] );

				return response()->json($response, $http_status);
				
			}catch(\Exception $e){
				$http_status = 505;
				$response = [
					"status" => "OK",
					"message" => $e->getMessage(),
				];

				$reponse_time = [
					"type"=>"CREDITPROCESS",
					"startTime"=>$this->startTime,
					"endTime"=> microtime(true),
					"response"=>microtime(true) - $this->startTime 
				];
				 Helper::saveLog('SOLID_RESONSE_TIME_CREDIT', 2, json_encode($reponse_time), ["reponse_time" => microtime(true) - $this->startTime]);
				Helper::saveLog('SOLID_GAMING_CREDIT_CATCH', 2, file_get_contents("php://input"), $response);
				return response()->json($response, $http_status);
			}

		// }
		
	}

	public function debitAndCreditProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		// $body = [];
		// $response = [];
		// $client_response = [];
		// $is_idempotent = $this->_isIdempotent($json_data['transid'],"false","credit_idom");
		
		if( $this->_isIdempotent($json_data['transid'],"false","credit_idom")) {
			return  $this->_isIdempotent($json_data['transid'],"false","credit_idom")->mw_response;
		}
		// if($this->_isIdempotent($json_data['transid'], "false", "credit_idom" )) {
		// 	return $this->_isIdempotent($json_data['transid'], "false", "credit_idom" )->mw_response;
		// }
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
				// GameRound::create($json_data['roundid'], $client_details->token_id);
				
				// Check if the game is available for the client
				// $subscription = new GameSubscription();
				// $client_game_subscription = $subscription->check($client_details->client_id, 1, $json_data['gamecode']);

				/*if(!$client_game_subscription) {
					$http_status = 404;
					$response = [
							"errorcode" =>  "GAME_NOT_FOUND",
							"errormessage" => "Game not found",
						];
				}
				else
				{*/
					// $exist_round_create = $this->create_Check($json_data['roundid'], $client_details->token_id);

					//create ROUND
					$this->create_Check($json_data['roundid'], $client_details->token_id);

					// if (!$this->create_Check($json_data['roundid'], $client_details->token_id)) {
					
					// 	// If round is not found
					// 	$http_status = 404;
					// 	$response = [
					// 		"errorcode" =>  "ROUND_NOT_FOUND",
					// 		"errormessage" => "Round not found",
					// 	];
					// }
					// else
					// {
						// if(!GameRound::check($json_data['roundid'])) {
						// 	$http_status = 400;
						// 	$response = [
						// 		"errorcode" =>  "ROUND_ENDED",
						// 		"errormessage" => "Game round have already been closed",
						// 	];
						// }
						// else
						// {
							/*DEBIT*/

							$json_data['income'] = $json_data['betamount'];
							$json_data['amount'] = $json_data['betamount'];

							// $game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));
							$game_details = $this->findGameDetails($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

							// $debit_game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);
							$debit_game_transaction_id = $this->saveGameTransaction('debit', $json_data, $game_details,$client_details->player_token);

							// $debit_game_trans_ext_id = ProviderHelper::createGameTransExtV2($debit_game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['betamount'], 1);
							$debit_game_trans_ext_id = $this->createGameTransExt($debit_game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['betamount'], 1);

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

								// $game_details = Game::find($json_data["gamecode"], config("providerlinks.solid.PROVIDER_ID"));

								$json_data['income'] = $json_data["winamount"];
								$json_data['amount'] = $json_data["winamount"];

								$credit_game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

								$credit_game_trans_ext_id = ProviderHelper::createGameTransExtV2($credit_game_transaction_id, $json_data['transid'], $json_data['roundid'], $json_data['winamount'], 2);

								// change $json_data['roundid'] to $credit_game_transaction_id
		               			$credit_client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['winamount'], $game_details->game_code, $game_details->game_name, $credit_game_trans_ext_id, $credit_game_transaction_id, 'credit');

								if(isset($credit_client_response->fundtransferresponse->status->code) 
									&& $credit_client_response->fundtransferresponse->status->code == "200") {

									// if(array_key_exists("roundended", $json_data)) {
									// 	if ($json_data["roundended"] == "true") {
									// 		GameRound::end($json_data['roundid']);
									// 	}
									// }
									
									$response = [
										"status" => "OK",
										"currency" => $client_details->default_currency,
										"balance" => $credit_client_response->fundtransferresponse->balance,
									];

								}
							}
				
							ProviderHelper::updatecreateGameTransExt($credit_game_trans_ext_id, $json_data, $response, $credit_client_response->requestoclient, $credit_client_response, $json_data);
						// }
					// }
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
						// $game_details = GameTransaction::find($json_data['originaltransid']); //transid
						$game_details = Game::findby('trans_id', $json_data["originaltransid"], config("providerlinks.solid.PROVIDER_ID"));
						
						// If transaction is not found
						if(!$game_details) {
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
							// $game_details = Game::findby('trans_id', $json_data["originaltransid"], config("providerlinks.solid.PROVIDER_ID"));

							// if refund is not exisiting, create one
							/*$game_transaction_id = GameTransaction::find_refund($json_data["originaltransid"]);

							if(!$game_transaction_id) {
								$game_transaction_id = GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $client_details);
							}*/

							$game_transaction_id = GameTransaction::solid_rollback($json_data, $game_details->game_trans_id);

							$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transid'], $json_data['roundid'], $game_details->bet_amount, 3);

							// change $json_data['roundid'] to $game_transaction_id
	               			$client_response = ClientRequestHelper::fundTransfer($client_details, $game_details->bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);

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
									
									/*$game_transaction_id = GameTransaction::save('rollback', $json_data, $value, $client_details, $client_details);*/
									$json_data['originaltransid'] = $value->provider_trans_id;
									$game_transaction_id = GameTransaction::solid_rollback($json_data,$game_details->game_trans_id);

									$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $value->provider_trans_id, $value->round_id, $value->bet_amount, 3);

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

				$client_response = $this->playerDetailsCall($client_details);
				
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

	private function _isIdempotent($transaction_id, $is_rollback = false,$credit_debit = false) {
		$result = false;
		/*$query = DB::table('game_transaction_ext')
								->select('mw_response')
								->where('provider_trans_id', $transaction_id);*/

		$query_str = "SELECT mw_response FROM game_transaction_ext WHERE provider_trans_id = '".$transaction_id."' LIMIT 1" ;
		
		if ($is_rollback == true) {
			$query_str = "SELECT mw_response FROM game_transaction_ext WHERE provider_trans_id = '".$transaction_id."' AND game_transaction_type = 3 LIMIT 1" ;
		}

		if($credit_debit == "credit_idom") {
			$query_str = "SELECT mw_response FROM game_transaction_ext WHERE provider_trans_id = '".$transaction_id."' AND game_transaction_type = 2 LIMIT 1" ;
		}

         $transaction_exist = DB::select($query_str);

		/*if ($is_rollback == true) {
					$query->where([
				 		["game_transaction_type", "=", 3],
				 		["mw_response", "NOT LIKE", "%FAILED%"]
				 	]);
				}

		$query->limit(1);

		$transaction_exist = $query->first();*/

		if($transaction_exist) {
			$result = $transaction_exist[0];
		}

		return $result;								
	}

	// UPDATE PLAYER DETAILS
	public static function playerDetailsCall($client_details,$refreshtoken = false){
        // $client_details = ProviderHelper::getClientDetails('token', $player_token);
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
                    "datesent" => Helper::datesent(),
                    "gameid" => "",
                    "clientid" => $client_details->client_id,
                    "playerdetailsrequest" => [
                        "player_username"=>$client_details->username,
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $client_details->player_token,
                        "gamelaunch" => true,
                        "refreshtoken" => $refreshtoken
                    ]
                ];
            
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode($datatosend)]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response;
            }catch (\Exception $e){
               return 'false';
            }
        }else{
            return 'false';
        }
    }


    public function create_Check($round_id, $token_id) {
    	$data = ["round_id" => $round_id, "token_id" => $token_id];
		DB::table('game_rounds')->insert($data);
		// $check_if_round_exist = DB::table('game_rounds')
		// 						->where('round_id', $round_id)
		// 						->first();
		// if(!$check_if_round_exist) {
		// 	$data = ["round_id" => $round_id, "token_id" => $token_id];
		// 	DB::table('game_rounds')->insert($data);
		// }
		
		// $check_if_round_exist = DB::select("SELECT round_id, status_id FROM game_rounds WHERE round_id = '".$round_id."' ");
		// $count = count($check_if_round_exist);
		// if($count == 0) { // INSERT
		// 	$data = ["round_id" => $round_id, "token_id" => $token_id];
		// 	DB::table('game_rounds')->insert($data);
		// 	return true;
		// } else {

		// 	if ($check_if_round_exist[0]->status_id == 5) {
		// 		return false; // means the process done
		// 	}else {
		// 		return true;
		// 	}

		// }

	}

	public function findGameDetails($game_code, $provider_id = 0) {
		$game_details = DB::select("SELECT game_name, game_code, game_id FROM games WHERE game_code = '".$game_code."' AND provider_id = '".$provider_id."' LIMIT 1");
		return count($game_details) > 0 ? $game_details[0] : false;
	}


	public function update($request_data) {

		// $game_details = DB::table("game_transactions AS g")
		// 		 ->where("g.round_id", $request_data['roundid'])
		// 		 ->first();
		$game_details = DB::select("SELECT * FROM game_transactions WHERE round_id = '".$request_data["roundid"]."' LIMIT 1");
		
		$income = $game_details[0]->income; 
		$win = $game_details[0]->win;
		$pay_amount = $game_details[0]->pay_amount;
		$entry_id = $game_details[0]->entry_id;
		
		if($request_data["amount"] > 0.00) {
			$win = 1;
			$pay_amount = $game_details[0]->pay_amount + $request_data["amount"];
			$income = $game_details[0]->bet_amount - $pay_amount;
			$entry_id = 2;
		}

        $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_details[0]->game_trans_id)
                ->update(['pay_amount' => $pay_amount, 'income' => $income, 'win' => $win, 'entry_id' => $entry_id]);
                
		return ($game_details ? $game_details[0]->game_trans_id : false);
	}

	public function saveGameTransaction($method, $request_data, $game_data,$token_id) {
		/*var_dump($request_data); die();*/
		$trans_data = [
					"token_id" => $token_id,
					"game_id" => $game_data->game_id,
					"round_id" => $request_data["roundid"],
					"income" => $request_data["income"]
				];

		switch ($method) {
		    case "debit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = $request_data["amount"];
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
					// check if this is a free round
					if(array_key_exists('free_round_data', $request_data)) {
						$trans_data["payout_reason"] = json_encode($request_data["free_round_data"]);
					}
		        break;
		    case "credit":
			        $trans_data["provider_trans_id"] = $request_data["transid"];
			        $trans_data["bet_amount"] = 0;
			        $trans_data["win"] = 1;
			        $trans_data["pay_amount"] = abs($request_data["amount"]);
			        $trans_data["entry_id"] = 2;
			        $trans_data["payout_reason"] = $request_data["reason"];
		        break;
		    case "rollback":
		    		$trans_data["provider_trans_id"] = (array_key_exists('transid', $request_data) ? $request_data["transid"] : '');
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 1;
					$trans_data["pay_amount"] = $game_data->bet_amount;
					$trans_data["entry_id"] = 3;
					$trans_data["payout_reason"] = "Rollback of transaction ID: ".$game_data->game_trans_id;
		        break;
		    default:
		}
		$id = DB::table('game_transactions')->insertGetId($trans_data);
		return $id; 
	}


	public function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request='FAILED', $mw_response='FAILED', $mw_request='FAILED', $client_response='FAILED', $transaction_detail='FAILED', $general_details=null){
		// DB::enableQueryLog();
		$gametransactionext = array(
			"game_trans_id" => $game_trans_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_type,
			"provider_request" => json_encode($provider_request),
			"mw_response" =>json_encode($mw_response),
			"mw_request"=>json_encode($mw_request),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail),
			"general_details" =>json_encode($general_details)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		// Helper::saveLog('createGameTransExtV2', 999, json_encode(DB::getQueryLog()), "TIME createGameTransExtV2");
		return $gamestransaction_ext_ID;
	}




}
