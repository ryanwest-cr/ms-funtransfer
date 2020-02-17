<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Models\PlayerWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use DB;

class FundTransferController extends Controller
{
    public function __construct(){

		$this->middleware('oauth', ['except' => ['index']]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function process(Request $request) {
		$arr_result = [
						"fundtransferresponse" => [
							"status" => [
								"success" => false,
								"message" => "Insufficient balance.",
							],
								"balance" => false,
								"currencycode" => false
							]
						];

		if(!$this->hasInput($request)) {
			$arr_result["fundtransferresponse"]["status"]["message"] = "Request body is empty";
		}
		else
		{
			if($request->get("type") != "fundtransferrequest") {
				$arr_result["fundtransferresponse"]["status"]["message"] = "Invalid request.";
			}
			else
			{
				$token = $request->get("fundtransferrequest")["playerdetails"]["token"];
				$amount = $request->get("fundtransferrequest")["fundinfo"]["amount"];
				/*DB::enableQueryLog();*/
				
				$player_details = PlayerSessionToken::select("player_id")->where("token", $token)->first();

				if (!$player_details) {
					$arr_result["fundtransferresponse"]["status"]["message"] = "Player token is expired.";
				}
				else
				{
					$player_id = $player_details->player_id;
					$player_wallet = PlayerWallet::select("balance")->where("player_id", $player_id)->first();
					
					if (!$player_wallet) {
						$arr_result["fundtransferresponse"]["status"]["message"] = "Player not found..";
					}
					else
					{
						$player_current_balance = $player_wallet->balance;
						$amount_to_update = number_format((float)$player_current_balance + $amount, 2, '.', '');

						/*$query = DB::getQueryLog();*/
						/*print_r($query);*/
						/*DB::enableQueryLog();*/
						$transactiion_result = DB::table("player_wallet")
								        ->where("player_id", $player_id) 
								        ->limit(1)
								        ->update(array("balance" => $amount_to_update));
						/*$query = DB::getQueryLog();
						print_r($query);*/
						
						$arr_result = [
										"fundtransferresponse" =>  
										[
											"status" =>  [
											"success" =>  true,
											"message" =>  "Transaction successful."
										],
											"balance" =>  $amount_to_update,
											"currencycode" =>  "USD"
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
