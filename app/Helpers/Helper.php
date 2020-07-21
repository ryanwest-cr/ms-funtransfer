<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use Carbon\Carbon;

class Helper
{
	
	public static function datesent(){
		$date = Carbon::now();
		return $date->toDateTimeString();
	}

	public static function auth_key($api_key, $access_token) {
		$result = false;


		if($api_key == md5(env('API_KEY').$access_token)) {
			$result = true;
		}

		return $result;
	}

	public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
					"method_name" => $method,
					"provider_id" => $provider_id,
					"request_data" => json_encode(json_decode($request_data)),
					"response_data" => json_encode($response_data)
				];
		DB::table('seamless_request_logs')->insert($data);
	}

	public static function saveClientLog($method, $provider_id = 0, $sent_data, $response_data) {
		$data = [
					"method_name" => $method,
					"provider_id" => $provider_id,
					"sent_data" => $sent_data,
					"response_data" => json_encode($response_data)
				];

		DB::table('seamless_sent_logs')->insert($data);
	}

	/* NEW 061620 */
	public static function findGameDetails($type, $provider_id, $identification) {
		    $game_details = DB::table("games as g")
				->leftJoin("providers as p","g.provider_id","=","p.provider_id");
				
		    if ($type == 'game_code') {
				$game_details->where([
			 		["g.provider_id", "=", $provider_id],
			 		["g.game_code",'=', $identification],
			 	]);
			}
			$result= $game_details->first();
	 		return $result;
	}

    /* ERAIN 
 	 * Added new $income 05-08-20
 	 * $income=null
     */		
    public static function saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
		$data = [
					"token_id" => $token_id,
					"game_id" => $game_id,
					"round_id" => $round_id,
					"bet_amount" => $bet_amount,
					"provider_trans_id" => $provider_trans_id,
					"pay_amount" => $payout,
					"income" => $income,
					"entry_id" => $entry_id,
					"win" => $win,
					"transaction_reason" => $transaction_reason,
					"payout_reason" => $payout_reason
				];
		$data_saved = DB::table('game_transactions')->insertGetId($data);
		return $data_saved;
	}

	public static function saveGame_trans_ext($trans_id, $transaction_detail) {
		$data = [
					"game_trans_id" => $trans_id,
					"transaction_detail" => $transaction_detail
				];
		$transaction_saved = DB::table('game_transaction_ext')->insertGetId($data);
		return $transaction_saved;
	}


	public static function save_player($client_id, $client_player_id, $username,  $email, $display_name, $lang='en', $currency='USD') {
		$data = [
					"client_id" => $client_id,
					"client_player_id" => $client_player_id,
					"username" => $username,
					"email" => $email,
					"display_name" => $display_name
				];
		return DB::table('players')->insertGetId($data);
	}
	public static function checkPlayerExist($client_id, $client_player_id, $username,  $email, $display_name,$token,$player_ip_address=false){
		$player = DB::table('players')
					->where('client_id',$client_id)
					->where('client_player_id',$client_player_id)
					// ->where('username',$username)
					->first();
		if($player){
			return Helper::createPlayerSessionToken($player->player_id,$token,$player_ip_address);
		}
		else{
			$player_id=Helper::save_player($client_id,$client_player_id,$username,$email,$display_name);
			return Helper::createPlayerSessionToken($player_id,$token,$player_ip_address);
		}
	}
	public static function createPlayerSessionToken($player_id,$token,$player_ip_address){
		$player_session_token = array(
			"player_id" => $player_id,
			"player_token" => $token,
			"player_ip_address" => $player_ip_address,
			"status_id" => 1
		);
		DB::table('player_session_tokens')->insert($player_session_token);
		return $token;
	}
	public static function savePLayerGameRound($game_code,$player_token){
		$game = DB::table("games")->where("game_code",$game_code)->first();
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
					$trans_data["win"] = 0;
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
	public static function getGameTransaction($player_token,$game_round){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.round_id",$game_round)
				->first();
		return $game;
	}
	public static function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			$game = DB::table('game_transaction_ext')
				->where('provider_trans_id',$provider_transaction_id)
				->where('round_id',$round_id)
				->where('game_transaction_type',$type)
				->first();
		}
		else{
			$game = DB::table('game_transaction_ext')
				->where('provider_trans_id',$provider_transaction_id)
				->first();
		}
		return $game ? true :false;
	}
	public static function getBalance($client_details){
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode(
                            [
                                "access_token" => $client_details->client_access_token,
                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                "type" => "playerdetailsrequest",
                                "datesent" => "",
                                "gameid" => "",
                                "clientid" => $client_details->client_id,
                                "playerdetailsrequest" => [
                                	"client_player_id" => $client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "false",
                                    "refreshtoken" => "false"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response->playerdetailsresponse->balance;
        }
	}
	public static function createICGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transactionId"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>array_key_exists("roundId",$provider_request)?$provider_request["roundId"]:0,
			"amount" =>round($provider_request["amount"]/100,2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}
	public static function createBNGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["uid"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["args"]["round_id"],
			"amount" =>$game_transaction_type==1?round($provider_request["args"]["bet"],2):round($provider_request["args"]["win"],2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}
	public static function createGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request->gameId,
			"amount" =>$provider_request->amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request->getContent()),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		if($provider_request->has("id")){
			$gametransactionext["provider_trans_id"] = $provider_request->id;
		}
		else{
			$gametransactionext["provider_trans_id"] = 0;
		}
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}
	public static function createMannaGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_request['transaction_id'],
			"round_id" =>$provider_request['round_id'],
			"amount" =>$provider_request['amount'],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
	
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}
	public static function updateGameTransaction($existingdata,$request_data,$type){
		switch ($type) {
			case "debit":
					$trans_data["bet_amount"] = $existingdata->bet_amount + $request_data["amount"];
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
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
}