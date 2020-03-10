<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;

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

		$response = [
						"errorcode" =>  "INVALID_TOKEN",
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpstatus" => "404"
					];

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
							]]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());


			$response = [
				"status" => "OK",
				"brand" => "BETRNKMW",
				"playerid" => "1",
				"currency" => $client_response->playerdetailsresponse->currencycode,
				"balance" => $client_response->playerdetailsresponse->balance,
				"testaccount" => false,
				"wallettoken" => "",
				"country" => "",
				"affiliatecode" => "",
				"displayname" => $client_response->playerdetailsresponse->accountname,
			];

		}

		Helper::saveLog('authentication', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function getPlayerDetails(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
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
			        	[
			        		"access_token" => $client_details->client_access_token,
							"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							"type" => "playerdetailsrequest",
							"datesent" => "",
							"gameid" => "",
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $client_details->player_token,
								"gamelaunch" => true
							]]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());			

			$response = [
				"status" => "OK",
				"brand" => "BETRNKMW",
				"currency" => $client_response->playerdetailsresponse->currencycode,
				"testaccount" => false,
				"country" => "",
				"affiliatecode" => "",
				"displayname" => $client_response->playerdetailsresponse->accountname,
			];
		}

		Helper::saveLog('playerdetails', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
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
			        	[
			        		"access_token" => $client_details->client_access_token,
							"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							"type" => "playerdetailsrequest",
							"datesent" => "",
							"gameid" => "",
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $client_details->player_token,
								"gamelaunch" => true
							]]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->playerdetailsresponse->currencycode,
				"balance" => $client_response->playerdetailsresponse->balance,
			];
		}

		Helper::saveLog('balance', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function debitProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token AS player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
						 ->first();
			 
		if ($client_details) {
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
								"token" => $client_details->player_token
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => "debit",
							      "transferid" => "",
							      "currencycode" => $client_details->currency,
							      "amount" => "-".$json_data["amount"]
							]
						  ]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->fundtransferresponse->currencycode,
				"balance" => $client_response->fundtransferresponse->balance,
			];
		}

		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function creditProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token AS player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
						 ->first();
			 
		if ($client_details) {
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
								"token" => $client_details->player_token
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => "debit",
							      "transferid" => "",
							      "currencycode" => $client_details->currency,
							      "amount" => $json_data["amount"]
							]
						  ]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->fundtransferresponse->currencycode,
				"balance" => $client_response->fundtransferresponse->balance,
			];
		}
	
		Helper::saveLog('credit', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	public function debitAndCreditProcess(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		
		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token AS player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
						 ->first();
			 
		if ($client_details) {
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
								"token" => $client_details->player_token
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => "debit",
							      "transferid" => "",
							      "currencycode" => $client_details->currency,
							      "amount" => ("-".$json_data["betamount"] + $json_data["winamount"])
							]
						  ]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->fundtransferresponse->currencycode,
				"balance" => $client_response->fundtransferresponse->balance,
			];
		}
		
		Helper::saveLog('debitandcredit', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);
	}

	public function endPlayerRound(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "The provided playerid don’t exist.",
						"httpstatus" => "404"
					];

		$player_id = $json_data["playerid"];

		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.player_id", $player_id)
						 ->where("pst.status_id", 1)
						 ->first();

		if ($client_details) {
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
								"token" => $client_details->player_token
							],
							"fundinfo" => [
							      "gamesessionid" => "",
							      "transactiontype" => "debit",
							      "transferid" => "",
							      "currencycode" => $client_details->currency,
							      "amount" => 0
							]
						  ]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->fundtransferresponse->currencycode,
				"balance" => $client_response->fundtransferresponse->balance,
			];
		}

		Helper::saveLog('playerdetails', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}


}
