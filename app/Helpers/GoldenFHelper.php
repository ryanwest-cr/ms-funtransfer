<?php

namespace App\Helpers;

use App\Helpers\Helper;
use App\Services\AES;
use GuzzleHttp\Client;
use DB;
class GoldenFHelper{

	
	# Transfer Wallet Helper Use TransferWallerHlper Instead


	// public static function savePLayerGameRound($game_code,$player_token,$sub_provider_name){
	// 	$sub_provider_id = DB::table("sub_providers")->where("sub_provider_name",$sub_provider_name)->first();
	// 	Helper::saveLog('SAVEPLAYERGAME(ICG)', 12, json_encode($sub_provider_id), $sub_provider_name);
	// 	$game = DB::table("games")->where("game_code",$game_code)->where("sub_provider_id",$sub_provider_id->sub_provider_id)->first();
	// 	$player_game_round = array(
	// 		"player_token" => $player_token,
	// 		"game_id" => $game->game_id,
	// 		"status_id" => 1
	// 	);
	// 	DB::table("player_game_rounds")->insert($player_game_round);
	// }

    // public static function getInfoPlayerGameRound($player_token){
	// 	$game = DB::table("player_game_rounds as pgr")
	// 			->leftJoin("player_session_tokens as pst","pst.player_token","=","pgr.player_token")
	// 			->leftJoin("games as g" , "g.game_id","=","pgr.game_id")
	// 			->leftJoin("players as ply" , "pst.player_id","=","ply.player_id")
	// 			->where("pgr.player_token",$player_token)
	// 			->first();
	// 	return $game ? $game : false;
	// }
	// public static function getGameTransaction($player_token,$game_round){
	// 	$game = DB::table("player_session_tokens as pst")
	// 			->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
	// 			->where("pst.player_token",$player_token)
	// 			->where("gt.round_id",$game_round)
	// 			->first();
	// 	return $game;
    // }
    // public static function updateGameTransactionExt($gametransextid,$amount,$mw_request,$mw_response,$client_response){
	// 	$gametransactionext = array(
    //         "amount" => $amount,
	// 		"mw_request"=>json_encode($mw_request),
	// 		"mw_response" =>json_encode($mw_response),
	// 		"client_response" =>json_encode($client_response),
	// 	);
	// 	DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	// }
	// public static function createGameTransaction($method, $request_data, $game_data, $client_data){
	// 	$trans_data = [
	// 		"token_id" => $client_data->token_id,
	// 		"game_id" => $game_data->game_id,
	// 		"round_id" => $request_data["roundid"]
	// 	];

	// 	switch ($method) {
	// 		case "debit":
	// 				$trans_data["provider_trans_id"] = $request_data["transid"];
	// 				$trans_data["bet_amount"] = abs($request_data["amount"]);
	// 				$trans_data["win"] = 5;
	// 				$trans_data["pay_amount"] = 0;
	// 				$trans_data["entry_id"] = 1;
	// 				$trans_data["income"] = 0;
	// 			break;
	// 		case "credit":
	// 				$trans_data["provider_trans_id"] = $request_data["transid"];
	// 				$trans_data["bet_amount"] = 0;
	// 				$trans_data["win"] = $request_data["win"];
	// 				$trans_data["pay_amount"] = abs($request_data["amount"]);
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["payout_reason"] = $request_data["payout_reason"];
	// 			break;
	// 		case "refund":
	// 				$trans_data["provider_trans_id"] = $request_data["transid"];
	// 				$trans_data["bet_amount"] = 0;
	// 				$trans_data["win"] = 0;
	// 				$trans_data["pay_amount"] = 0;
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
	// 			break;

	// 		default:
	// 	}
	// 	/*var_dump($trans_data); die();*/
	// 	return DB::table('game_transactions')->insertGetId($trans_data);			
	// }
    // public static function updateGameTransaction($existingdata,$request_data,$type){
	// 	switch ($type) {
	// 		case "debit":
    //                 $trans_data["win"] = $existingdata->win;
    //                 $trans_data["bet_amount"] = $existingdata->bet_amount+$request_data["amount"];
	// 				$trans_data["pay_amount"] = $existingdata->pay_amount;
	// 				$trans_data["income"]= ($existingdata->bet_amount+$request_data["amount"])-$existingdata->pay_amount;
	// 				$trans_data["entry_id"] = $existingdata->entry_id;
	// 			break;
	// 		case "credit":
	// 				$trans_data["win"] = $request_data["win"];
	// 				$trans_data["pay_amount"] =  $existingdata->pay_amount+abs($request_data["amount"]);
	// 				$trans_data["income"]=$existingdata->bet_amount-($existingdata->pay_amount+$request_data["amount"]);
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["payout_reason"] = $request_data["payout_reason"];
	// 			break;
	// 		case "refund":
	// 				$trans_data["win"] = 4;
	// 				$trans_data["pay_amount"] = $existingdata->pay_amount+$request_data["amount"];
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["income"]= $existingdata->bet_amount-$existingdata->pay_amount+$request_data["amount"];
	// 				$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
	// 			break;

	// 		default:
	// 	}
	// 	/*var_dump($trans_data); die();*/
	// 	return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
	// }






	######################################################################################################################
	# ISOLATED FUNCTION FOR SINGLE DEBUGGING ON THE GO (ProviderHelper::class)
}

?>