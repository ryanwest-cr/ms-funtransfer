<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;

use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DB;

class PlayerDetailsController extends Controller
{
    public function __construct(){

		$this->middleware('oauth', ['except' => ['index']]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function show(Request $request) {
		$json_data = json_decode(file_get_contents("php://input"), true);

		$arr_result = [
						"playerdetailsresponse" =>  [
							"status" =>  [
								"success" =>  "false",
								"message" =>  "Invalid Request."
							]
						]
					];
		if(empty($json_data) || count($json_data) == 0 || sizeof($json_data) == 0) {
			$arr_result["playerdetailsresponse"]["status"]["message"] = "Request Body is Empty.";
		}
		else
		{
			$hash_key = $json_data["hashkey"];
			$access_token = $request->bearerToken();	

			if(!Helper::auth_key($hash_key, $access_token)) {
				$arr_result["playerdetailsresponse"]["status"]["message"] = "Authentication Mismatched.";
			}
			else
			{
				if($json_data["type"] != "playerdetailsrequest") {
					$arr_result["playerdetailsresponse"]["status"]["message"] = "Invalid Request.";
				}
				else
				{
					$player_session_token = $json_data["playerdetailsrequest"]["token"];

					$client_details = DB::table("clients")
									 ->leftJoin("player_session_tokens", "clients.id", "=", "player_session_tokens.client_id")
									 ->leftJoin("client_endpoints", "clients.id", "=", "client_endpoints.client_id")
									 ->leftJoin("client_access_tokens", "clients.id", "=", "client_access_tokens.client_id")
									 ->where("player_session_tokens.token", $player_session_token)
									 ->first();

					if (!$client_details) {
						$arr_result["playerdetailsresponse"]["status"]["message"] = "Invalid Endpoint.";
					}
					else
					{
						$client = new Client([
						    'headers' => [ 
						    	'Content-Type' => 'application/json',
						    	'Authorization' => 'Bearer '.$client_details->token
						    ]
						]);
						
						$response = $client->post($client_details->player_details_url,
						    ['body' => json_encode(
						        	["access_token" => $client_details->token,
										"hashkey" => md5($client_details->api_key.$client_details->token),
										"type" => $json_data["type"],
										"datesent" => $json_data["datesent"],
										"gameid" => $json_data["gameid"],
										"clientid" => $json_data["clientid"],
										"playerdetailsrequest" => [
											"token" => $json_data["playerdetailsrequest"]["token"],
											"gamelaunch" => $json_data["playerdetailsrequest"]["gamelaunch"]
										]]
						    )]
						);

						return var_export($response->getBody()->getContents(), true);
					}
				}
			}
		}
		
		echo json_encode($arr_result);
	}

}
