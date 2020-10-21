<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use App\Services\AES;
class FCHelper
{
	public static function AESEncode($data){
        $aes = new AES(config('providerlinks.fcgaming.AgentKey'));
        return $aes->AESEncode($data);
    }
    public static function AESDecode($data){
        $aes = new AES(config('providerlinks.fcgaming.AgentKey'));
        return $aes->AESdecode($data);
    }
    public static function addMember($player_id,$language){
        $reqdata = array(
            "MemberAccount"=>$player_id,
            "LanguageID"=>$language
        );
        $data = json_encode($reqdata);
        $Params = FCHelper::AESEncode($data);
        $sign = md5($data);
        $client = new Client();
        //return config('providerlinks.fcgaming.url').'/AddMember';
        $provider_response = $client->post(config('providerlinks.fcgaming.url').'/AddMember',
            ['form_params' => [
                "AgentCode" =>config('providerlinks.fcgaming.AgentCode'),
                "Currency" => "USD",
                "Params" => $Params,
                "Sign" => $sign
                ]
            ]
        );
        return json_decode($provider_response->getBody(),TRUE);
    }
    public static function loginGame($player_id,$game_code,$language,$exitURL){
        $reqdata = array(
            "MemberAccount"=>$player_id,
            "GameID"=>$game_code,
            "LanguageID"=>$language,
            "HomeUrl"=>$exitURL,
        );
        $data = json_encode($reqdata);
        $Params = FCHelper::AESEncode($data);
        $sign = md5($data);
        $client = new Client();
        
        $provider_response = $client->post(config('providerlinks.fcgaming.url').'/Login',
            ['form_params' => [
                "AgentCode" =>config('providerlinks.fcgaming.AgentCode'),
                "Currency" => "USD",
                "Params" => $Params,
                "Sign" => $sign
                ]
            ]
        );
        return json_decode($provider_response->getBody(),TRUE);
    }
    public static function getGameTransactionupdate($player_token,$game_round){
		DB::enableQueryLog();
		// $game = DB::table("player_session_tokens as pst")
		// 		->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
		// 		->where("pst.player_token",$player_token)
		// 		->where("gt.round_id",$game_round)
		// 		->first();
		$game = DB::select("SELECT
						entry_id,bet_amount,game_trans_id
						FROM game_transactions g
						INNER JOIN player_session_tokens USING (token_id)
						WHERE player_token = '".$player_token."' and round_id = '".$game_round."'");
		Helper::saveLog('TIMEgetGameTransaction(EVG)', 189, json_encode($game), "DB TIME");
		Helper::saveLog('TIMEgetGameTransaction(EVG)', 189, json_encode(DB::getQueryLog()), "DB TIME");
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
    public static function createFCGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["BankID"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["RecordID"],
			"amount" =>$game_transaction_type==1?round($provider_request["Bet"],2):round($provider_request["Win"],2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
    }
    public static function updateFCGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
    }
    public static function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
        $game = DB::table('game_transaction_ext')
            ->where('provider_trans_id',$provider_transaction_id)
            ->first();
		return $game;
    }
    public static function getInfoPlayerGameRound($player_token){
		$game = DB::table("player_game_rounds as pgr")
				->leftJoin("player_session_tokens as pst","pst.player_token","=","pgr.player_token")
				->leftJoin("games as g" , "g.game_id","=","pgr.game_id")
				->leftJoin("players as ply" , "pst.player_id","=","ply.player_id")
				->where("pgr.player_token",$player_token)
				->first();
		return $game ? $game : false;
    }
    public static function getGameTransaction($player_token,$provider_trans_id){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.provider_trans_id",$provider_trans_id)
				->first();
		return $game;
	}
}