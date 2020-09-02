<?php

namespace App\Helpers;

use App\Services\AES;
use GuzzleHttp\Client;
use DB;
class MGHelper{

    public static function launchGame($token,$player_id,$game_code){
        $key = "LUGTPyr6u8sRjCfh";
        $aes = new AES($key);
        $player_id = $player_id;
        $providerlinks = "https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions";
        $http = new Client();
        $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions",[
            'form_params' => [
                'platform' => "desktop",
                'langCode' => "en-EN",//needd to be dynamic
                'contentCode' => $game_code,//temporary this is the game code
            ]
            ,
            'headers' =>[
                'Authorization' => 'Bearer '.MGHelper::stsTokenizer(),
                'Accept'     => 'application/json' 
            ]
        ]);

        $url = json_decode((string) $response->getBody(), true)["gameURL"];
        $data = array(
            "url" => urlencode($url),
            "token" => $token,
            "player_id" => $player_id
        );
        $encoded_data = $aes->AESencode(json_encode($data));
        return "https://play.betrnk.games/loadgame/microgaming?param=".urlencode($encoded_data);
    }
     public static function stsTokenizer(){
        $http = new Client();
        $response = $http->post('https://sts-tigergaming.k2net.io/connect/token', [
            'form_params' => [
                'grant_type' => config('providerlinks.microgaming.grant_type'),
                'client_id' => config('providerlinks.microgaming.client_id'),
                'client_secret' => config('providerlinks.microgaming.client_secret'),
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["access_token"];
     }
     public static function getGameTransaction($player_token,$game_round){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.round_id",$game_round)
				->first();
		return $game;
    }
    public static function updateGameTransactionExt($gametransextid,$amount,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
            "amount" => $amount,
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
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
     public static function createMGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transid"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["roundid"],
			"amount" =>$provider_request["amount"],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
    }
}

?>