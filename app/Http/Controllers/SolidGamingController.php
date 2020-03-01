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

	public function authPlayer(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$response = [
						"errorcode" =>  "INVALID_TOKEN",
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpStatus" => "404"
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
				"player_id" => "1",
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


}
