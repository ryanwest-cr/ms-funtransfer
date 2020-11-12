<?php

namespace App\Helpers;

use App\Services\AES;
use GuzzleHttp\Client;
use DB;
class GoldenFHelper{

	public static function playerDetailsCall($client_details, $refreshtoken=false){
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $datatosend = ["access_token" => $client_details->client_access_token,
            "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
            "type" => "playerdetailsrequest",
            "datesent" => Helper::datesent(),
            "clientid" => $client_details->client_id,
            "playerdetailsrequest" => [
                "player_username"=>$client_details->username,
                "client_player_id" => $client_details->client_player_id,
                "token" => $client_details->player_token,
                "gamelaunch" => true,
                "refreshtoken" => $refreshtoken
            ]
        ];
        try{    
            $guzzle_response = $client->post($client_details->player_details_url,
                ['body' => json_encode($datatosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            return $client_response;
        }catch (\Exception $e){
           Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
           return 'false';
        }
    }

	public static function savePLayerGameRound($game_code,$player_token,$sub_provider_name){
		$sub_provider_id = DB::table("sub_providers")->where("sub_provider_name",$sub_provider_name)->first();
		Helper::saveLog('SAVEPLAYERGAME(ICG)', 12, json_encode($sub_provider_id), $sub_provider_name);
		$game = DB::table("games")->where("game_code",$game_code)->where("sub_provider_id",$sub_provider_id->sub_provider_id)->first();
		$player_game_round = array(
			"player_token" => $player_token,
			"game_id" => $game->game_id,
			"status_id" => 1
		);
		DB::table("player_game_rounds")->insert($player_game_round);
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
	public static function getGameTransaction($player_token,$game_round){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.round_id",$game_round)
				->first();
		return $game;
    }
    public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		return DB::table('seamless_request_logs')->insertGetId($data);
		// return DB::table('debug')->insertGetId($data);
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
	public static function createGameTransaction($method, $request_data, $game_data, $client_data){
		$trans_data = [
			"token_id" => $client_data->token_id,
			"game_id" => $game_data->game_id,
			"round_id" => $request_data["roundid"]
		];

		switch ($method) {
			case "debit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = abs($request_data["amount"]);
					$trans_data["win"] = 5;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
					$trans_data["income"] = 0;
				break;
			case "credit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->insertGetId($trans_data);			
	}
    public static function updateGameTransaction($existingdata,$request_data,$type){
		switch ($type) {
			case "debit":
                    $trans_data["win"] = $existingdata->win;
                    $trans_data["bet_amount"] = $existingdata->bet_amount+$request_data["amount"];
					$trans_data["pay_amount"] = $existingdata->pay_amount;
					$trans_data["income"]= ($existingdata->bet_amount+$request_data["amount"])-$existingdata->pay_amount;
					$trans_data["entry_id"] = $existingdata->entry_id;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] =  $existingdata->pay_amount+abs($request_data["amount"]);
					$trans_data["income"]=$existingdata->bet_amount-($existingdata->pay_amount+$request_data["amount"]);
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $existingdata->pay_amount+$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata->bet_amount-$existingdata->pay_amount+$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
	}

    public static function launchGame($token,$player_id,$game_code){
        $key = "LUGTPyr6u8sRjCfh";
        $aes = new AES($key);
        $player_id = $player_id;
        // $providerlinks = "https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions";
        // $http = new Client();
        // $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions",[
        //     'form_params' => [
        //         'platform' => "desktop",
        //         'langCode' => "en-EN",//needd to be dynamic
        //         'contentCode' => $game_code,//temporary this is the game code
        //     ]
        //     ,
        //     'headers' =>[
        //         'Authorization' => 'Bearer ',
        //         'Accept'     => 'application/json' 
        //     ]
        // ]);
        // $url = json_decode((string) $response->getBody(), true)["gameURL"];
        
        $url = 'https://showcase.codethislab.com/games/slot_arabian/';
        $data = array(
            "url" => urlencode($url),
            "token" => $token,
            "player_id" => $player_id
        );
        $encoded_data = $aes->AESencode(json_encode($data));
        // return "https://play.betrnk.games/loadgame/goldenf?param=".urlencode($encoded_data);
        return "http://play.betrnk.games:81/loadgame/goldenf?param=".urlencode($encoded_data);
    }
     // public static function stsTokenizer(){
 //        $http = new Client();
 //        $response = $http->post('https://sts-tigergaming.k2net.io/connect/token', [
 //            'form_params' => [
 //                'grant_type' => config('providerlinks.microgaming.grant_type'),
 //                'client_id' => config('providerlinks.microgaming.client_id'),
 //                'client_secret' => config('providerlinks.microgaming.client_secret'),
 //            ],
 //        ]);

 //        return json_decode((string) $response->getBody(), true)["access_token"];
 //     }
 //     public static function getGameTransaction($player_token,$game_round){
	// 	$game = DB::table("player_session_tokens as pst")
	// 			->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
	// 			->where("pst.player_token",$player_token)
	// 			->where("gt.round_id",$game_round)
	// 			->first();
	// 	return $game;
 //    }
 //    public static function updateGameTransactionExt($gametransextid,$amount,$mw_request,$mw_response,$client_response){
	// 	$gametransactionext = array(
 //            "amount" => $amount,
	// 		"mw_request"=>json_encode($mw_request),
	// 		"mw_response" =>json_encode($mw_response),
	// 		"client_response" =>json_encode($client_response),
	// 	);
	// 	DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	// }
 //    public static function updateGameTransaction($existingdata,$request_data,$type){
	// 	switch ($type) {
	// 		case "debit":
 //                    $trans_data["win"] = 0;
 //                    $trans_data["bet_amount"] = $existingdata->bet_amount+$request_data["amount"];
	// 				$trans_data["pay_amount"] = 0;
	// 				$trans_data["income"]=0;
	// 				$trans_data["entry_id"] = 1;
	// 			break;
	// 		case "credit":
	// 				$trans_data["win"] = $request_data["win"];
	// 				$trans_data["pay_amount"] = abs($request_data["amount"]);
	// 				$trans_data["income"]=$existingdata->bet_amount-$request_data["amount"];
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["payout_reason"] = $request_data["payout_reason"];
	// 			break;
	// 		case "refund":
	// 				$trans_data["win"] = 4;
	// 				$trans_data["pay_amount"] = $request_data["amount"];
	// 				$trans_data["entry_id"] = 2;
	// 				$trans_data["income"]= $existingdata->bet_amount-$request_data["amount"];
	// 				$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
	// 			break;

	// 		default:
	// 	}
	// 	/*var_dump($trans_data); die();*/
	// 	return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
	// }
 //     public static function createMGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
	// 	$gametransactionext = array(
	// 		"provider_trans_id" => $provider_request["transid"],
	// 		"game_trans_id" => $gametransaction_id,
	// 		"round_id" =>$provider_request["roundid"],
	// 		"amount" =>$provider_request["amount"],
	// 		"game_transaction_type"=>$game_transaction_type,
	// 		"provider_request" =>json_encode($provider_request),
	// 		"mw_request"=>json_encode($mw_request),
	// 		"mw_response" =>json_encode($mw_response),
	// 		"client_response" =>json_encode($client_response),
	// 	);
	// 	$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
	// 	return $gamestransaction_ext_ID;
 //    }
}

?>