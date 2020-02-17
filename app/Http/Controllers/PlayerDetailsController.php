<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use Illuminate\Http\Request;
use DB;

class PlayerDetailsController extends Controller
{
    public function __construct(){

		$this->middleware('oauth', ['except' => ['index']]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function show(Request $request) {
		$arr_result = [
						"playerdetailsresponse" =>  [
							"status" =>  [
								"success" =>  "false",
								"message" =>  "Invalid Request."
							]
						]
					];
		if(!$this->hasInput($request)) {
			$arr_result["playerdetailsresponse"]["status"]["message"] = "Request body is empty.";
		}
		else
		{
			$access_token = $request->get("access_token");
			$api_hashkey = $request->get("apihashkey");

			if($api_hashkey != md5(env('API_KEY').$access_token)) {
				$arr_result["playerdetailsresponse"]["status"]["message"] = "Authentication mismatched.";
			}
			else
			{
				if($request->get("type") != "playerdetailsrequest") {
					$arr_result["playerdetailsresponse"]["status"]["message"] = "Invalid request.";
				}
				else
				{
					$token = $request->get("playerdetailsrequest")["token"];

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

	private function hasInput(Request $request)
	{
	    if($request->has('_token')) {
	        return count($request->all()) > 1;
	    } else {
	        return count($request->all()) > 0;
	    }
	}
}
