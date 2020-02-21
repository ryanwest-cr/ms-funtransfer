<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;

use Illuminate\Http\Request;

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
			$arr_result["playerdetailsresponse"]["status"]["message"] = "Request body is empty.";
		}
		else
		{
			$hash_key = $json_data["hashkey"];
			$access_token = $json_data["access_token"];	

			if(!Helper::auth_key($hash_key, $access_token)) {
				$arr_result["playerdetailsresponse"]["status"]["message"] = "Authentication mismatched.";
			}
			else
			{
				if($json_data["type"] != "playerdetailsrequest") {
					$arr_result["playerdetailsresponse"]["status"]["message"] = "Invalid request.";
				}
				else
				{
					$token = $json_data["playerdetailsrequest"]["token"];

					/*DB::enableQueryLog();*/
					$player_details = DB::table("players")
									 ->leftJoin("player_details", "players.player_id", "=", "player_details.player_id")
									 ->leftJoin("player_wallets", "players.player_id", "=", "player_wallets.player_id")
									 ->leftJoin("player_session_tokens", "players.player_id", "=", "player_session_tokens.player_id")
									 ->leftJoin("currencies", "players.currency", "=", "currencies.id")
									 ->where("token", $token)
									 ->first();
						/*$query = DB::getQueryLog();
						print_r($query);*/
					if (!$player_details) {
						$arr_result["playerdetailsresponse"]["status"]["message"] = "Player not found.";
					}
					else
					{
						$arr_result = [
									"playerdetailsresponse" =>  [
										"status" =>  [
											"success" =>  "true",
											"message" =>  "Request successful."
										],
										"accountid" =>  $player_details->player_id,
										"accountname" =>  $player_details->first_name,
										"balance" =>  $player_details->balance,
										"currencycode" =>  $player_details->code
									]
								];
					}
					
				}
			}
			
		}
		

		echo json_encode($arr_result);
	}

}
