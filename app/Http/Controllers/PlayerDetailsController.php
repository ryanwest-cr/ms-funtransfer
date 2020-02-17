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
			if($request->get("type") != "playerdetailsrequest") {
				$arr_result["playerdetailsresponse"]["status"]["message"] = "Invalid request.";
			}
			else
			{
				$token = $request->get("playerdetailsrequest")["token"];

				/*DB::enableQueryLog();*/
				$playerdetails = DB::table("players")
								 ->leftJoin("player_details", "players.player_id", "=", "player_details.player_id")
								 ->leftJoin("player_wallet", "players.player_id", "=", "player_wallet.player_id")
								 ->leftJoin("player_session_tokens", "players.player_id", "=", "player_session_token.player_id")
								 ->leftJoin("currencies", "players.currency", "=", "currencies.id")
								 ->where("token", $token)
								 ->first();
					/*$query = DB::getQueryLog();
					print_r($query);*/
				
				$arr_result = [
								"playerdetailsresponse" =>  [
									"status" =>  [
										"success" =>  "true",
										"message" =>  "Request successful."
									],
									"accountid" =>  $playerdetails->id,
									"accountname" =>  $playerdetails->first_name,
									"balance" =>  $playerdetails->balance,
									"currencycode" =>  $playerdetails->code
								]
							];
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
