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

		$player_session_token = $json_data["token"];

		$client_details = DB::table("clients")
						 ->leftJoin("player_session_tokens", "clients.id", "=", "player_session_tokens.client_id")
						 ->leftJoin("client_endpoints", "clients.id", "=", "client_endpoints.client_id")
						 ->leftJoin("client_access_tokens", "clients.id", "=", "client_access_tokens.client_id")
						 ->where("player_session_tokens.token", $player_session_token)
						 ->first();

		if ($client_details) {
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->token
			    ]
			]);
			
			$guzzle_response = $client->post($client_details->player_details_url,
			    ['body' => json_encode(
			        	["access_token" => $client_details->token,
							"hashkey" => md5($client_details->api_key.$client_details->token),
							"type" => "playerdetailsrequest",
							"datesent" => "",
							"gameid" => "",
							"clientid" => $client_details->id,
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

			/*return var_export($response->getBody()->getContents(), true);*/
		}
	
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
						 ->select('p.id', 'p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token AS player_token' , 'pst.status_id', 'pd.first_name', 'c.api_key', 'cat.token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.id", "=", "pst.player_id")
						 ->leftJoin("player_details AS pd", "p.id", "=", "pd.player_id")
						 ->leftJoin("clients AS c", "c.id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.id", "=", "cat.client_id")
						 ->where("p.id", $player_id)
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
							"hashkey" => md5($client_details->api_key.$client_details->client_access_token),
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

			/*return var_export($response->getBody()->getContents(), true);*/
		}
	
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
						 ->select('p.id', 'p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token AS player_token' , 'pst.status_id', 'pd.first_name', 'c.api_key', 'cat.token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.id", "=", "pst.player_id")
						 ->leftJoin("player_details AS pd", "p.id", "=", "pd.player_id")
						 ->leftJoin("clients AS c", "c.id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.id", "=", "cat.client_id")
						 ->where("p.id", $player_id)
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
							"hashkey" => md5($client_details->api_key.$client_details->client_access_token),
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
						 ->select('p.id', 'p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token AS player_token' , 'pst.status_id', 'pd.first_name', 'c.api_key', 'cat.token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.id", "=", "pst.player_id")
						 ->leftJoin("player_details AS pd", "p.id", "=", "pd.player_id")
						 ->leftJoin("clients AS c", "c.id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.id", "=", "cat.client_id")
						 ->where("p.id", $player_id)
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
						  "hashkey" => md5($client_details->api_key.$client_details->client_access_token),
						  "type" => "fundtransferrequest",
						  "datetsent" => $json_data["datesent"],
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
							      "currencycode" => ,
							      "amount" => $json_data["fundtransferrequest"]["fundinfo"]["amount"]
							]
						  ]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			$response = [
				"status" => "OK",
				"currency" => $client_response->playerdetailsresponse->currencycode,
				"balance" => $client_response->playerdetailsresponse->balance,
			];
		}
	
		echo json_encode($response);

	}


}
