<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
class WazdanHelper
{
    public static function getGameTransaction($player_token,$game_round){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.round_id",$game_round)
				->first();
		return $game;
    }
    public static function getGameTransactionById($game_trans_id){
        $game = DB::table("game_transactions")
                ->where("game_trans_id",$game_trans_id)
				->first();
		return $game;
    }
    public static function getTransactionExt($provider_trans_id){
        $gametransaction = DB::table('game_transaction_ext')->where("provider_trans_id",$provider_trans_id)->first();
        return $gametransaction;
    }
    
    public static function gameTransactionExtChecker($provider_trans_id){
        $gametransaction = DB::table('game_transaction_ext')->where("provider_trans_id",$provider_trans_id)->first();
        return $gametransaction?true:false;
    }
    public static function updateGameTransaction($existingdata,$request_data,$type){
		switch ($type) {
			case "debit":
                    $trans_data["win"] = 0;
                    $trans_data["bet_amount"] = $existingdata->bet_amount+$request_data["amount"];
					$trans_data["pay_amount"] = 0;
					$trans_data["income"]=0;
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["income"]=$existingdata->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata->bet_amount-$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
	}
    public static function createWazdanGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transactionId"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["roundId"],
			"amount" =>$provider_request["amount"],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
    }
}