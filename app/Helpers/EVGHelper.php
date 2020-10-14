<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use App\Services\AES;
use App\Helpers\Helper;
class EVGHelper
{
    public static function createEVGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transaction"]["id"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["transaction"]["refId"],
			"amount" =>$provider_request["transaction"]["amount"],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
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
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->insertGetId($trans_data);			
	}
    public static function gameLaunch($token,$players_ip,$gamecode=null,$lang="en",$exit_url,$env){
		$client_details = EVGHelper::_getClientDetails("token",$token);
		if($env == 'test'){
			$game_details = explode("_",$gamecode);
		}
        if($env == 'production'){
			$game = EVGHelper::getGameDetails($gamecode,null,$env);
			$game_details[0] = $game->game_code;
			$game_details[1] = $game->info;
		}
		Helper::saveLog('gamedetails(EVG)', 74, json_encode($game_details), $env);
        if($client_details){
            $data = array(
                "uuid" => $token,
                "player"=> array(
                            "id"=> (string)$client_details->player_id,
                            "update"=>false,
                            "country"=>"US",
                            "language"=>$lang,
                            "currency"=> $client_details->default_currency,
                            "session" => array(
                                         "id"=>$token,
                                         "ip"=>$players_ip,

                            ),
                        ),
                "config"=> array(
                            "game" => array(
                                        "category"=>$game_details[1],
                                        "table"=>array(
                                                "id"=>$game_details[0]
                                        )
                            ),
                            "channel"=> array(
                                        "wrapped"=> false,
                                        "mobile"=> false
							),
							"urls" =>array(
										"lobby"=>$exit_url
							)
                        ),
            );
            Helper::saveLog('requestLaunchUrl(EVG)', 74, json_encode($data), $gamecode);
            $client = new Client();
            $provider_response = $client->post(config('providerlinks.evolution.ua2AuthenticationUrl'),
                ['body' => json_encode($data),
                ]
			);
			Helper::saveLog('responseLaunchUrl(EVG)', 74, json_encode($data), json_decode($provider_response->getBody(),TRUE));
            return config("providerlinks.evolution.host").json_decode($provider_response->getBody(),TRUE)["entry"];
        }
	}
	public static function getGameTransaction($game_round){
		$game = DB::table("game_transactions")
				->where("round_id",$game_round)
				->first();
		return $game;
	}
	public static function getGameDetails($game_code,$game_type=null,$env){
		if($env=='test'){
			$game = DB::table("games")
				->where("game_code",$game_code."_".$game_type)
				->first();
		}
		if($env=='production'){
			$game = DB::table("games")
				->where("game_code",$game_code)
				->first();
		}
		return $game ? $game : false;
	}
    public static function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
				 
				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $value],
				 		["pst.status_id", "=", 1]
				 	])->orderBy('pst.token_id','desc')->limit(1);
				}

				 $result= $query->first();

		return $result;
    }

}