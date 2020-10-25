<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use Carbon\Carbon;

class TransactionHelper
{

    public static function checkGameTransactionData($provider_transaction_id){
        DB::enableQueryLog();
		$game = DB::select("SELECT game_trans_ext_id
        FROM game_transaction_ext
        where provider_trans_id='".$provider_transaction_id."' limit 1");
        Helper::saveLog('checkGameTransactionData(TransactionHelper)', 189, json_encode(DB::getQueryLog()), "DB TIME");
		return $game;
    }
    public static function getGameTransaction($player_token,$game_round){
		$game = DB::select("SELECT
						entry_id,bet_amount,game_trans_id
						FROM game_transactions g
						INNER JOIN player_session_tokens USING (token_id)
						WHERE player_token = '".$player_token."' and round_id = '".$game_round."'");

		return $game;
    }
    public static function updateGameTransaction($existingdata,$request_data,$type){
		DB::enableQueryLog();
		switch ($type) {
			case "debit":
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["income"]=$existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["income"]=$existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;
			case "fail":
				$trans_data["win"] = 2;
				$trans_data["pay_amount"] = $request_data["amount"];
				$trans_data["entry_id"] = 1;
				$trans_data["income"]= 0;
				$trans_data["payout_reason"] = "Fail  transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"] .":Insuffecient Balance";
			break;
			default:
		}
		/*var_dump($trans_data); die();*/
		Helper::saveLog('TIMEupdateGameTransaction(EVG)', 189, json_encode(DB::getQueryLog()), "DB TIME");
		return DB::table('game_transactions')->where("game_trans_id",$existingdata[0]->game_trans_id)->update($trans_data);
	}
}
?>